<?php

namespace App\Services;

use App\Models\Company;
use App\Models\InventoryBastDocument;
use App\Models\Inventory;
use App\Models\InventoryStockTransaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InventoryBastService
{
    private const TRANSAKSI_KELUAR = 'keluar';
    private const PUBLIC_DISK = 'public';
    private const PDF_DIRECTORY = 'inventory/bast';
    private const SIGNATURE_DIRECTORY = 'inventory/bast/signatures';
    private const USER_AGENT_LIMIT = 1000;

    public function createForTransaction(InventoryStockTransaction $transaction, array $data, User $admin)
    {
        if ($transaction->jenis_transaksi !== self::TRANSAKSI_KELUAR) {
            throw ValidationException::withMessages([
                'bast' => 'Surat BAST hanya dapat dibuat untuk transaksi stok keluar.',
            ]);
        }

        if ($transaction->bastDocument) {
            return $transaction->bastDocument;
        }

        return DB::transaction(function () use ($transaction, $data, $admin) {
            $date = Carbon::parse($data['tanggal_surat'] ?? now());
            $admin->loadMissing('Jabatan');
            $transaction->loadMissing('inventory.jabatan', 'penerima.Jabatan');
            $inventoryDivision = $transaction->inventory->jabatan->nama_jabatan ?? null;
            $knownBy = !empty($data['known_by_user_id'])
                ? User::with('Jabatan')->find($data['known_by_user_id'])
                : null;
            $firstParty = !empty($data['first_party_user_id'])
                ? User::with('Jabatan')->find($data['first_party_user_id'])
                : null;
            $sender = $firstParty ?: $admin;
            $senderDivision = $firstParty
                ? ($firstParty->Jabatan->nama_jabatan ?? null)
                : ($inventoryDivision ?: ($admin->Jabatan->nama_jabatan ?? null));
            $receiverPosition = $transaction->penerima->Jabatan->nama_jabatan ?? $transaction->jabatan_penerima;
            $receiverDepartment = $transaction->departemen_penerima ?: $receiverPosition;
            $companyId = $transaction->company_id ?: optional($transaction->inventory)->company_id;

            $documentData = [
                'company_id' => $companyId,
                'inventory_stock_transaction_id' => $transaction->id,
                'nomor_surat' => $this->generateNumber($date, $companyId),
                'tanggal_surat' => $date->toDateString(),
                'nama_penerima' => $transaction->penerima->name ?? $transaction->penerima_barang,
                'jabatan_penerima' => $receiverPosition,
                'nama_penyerah' => $sender->name,
                'jabatan_penyerah' => $senderDivision ?: 'IT Engineer',
                'nama_mengetahui' => $knownBy ? $knownBy->name : ($data['nama_mengetahui'] ?? null),
                'known_by_user_id' => $knownBy->id ?? null,
                'first_party_user_id' => $firstParty->id ?? null,
            ];

            if ($this->supportsPartyDetails()) {
                $documentData['departemen_penerima'] = $receiverDepartment;
                $documentData['departemen_penyerah'] = $senderDivision ?: 'IT Engineer';
            }

            $document = InventoryBastDocument::create($documentData);

            return $this->storePdf($document);
        });
    }

    public function refreshFilesForInventory(Inventory $inventory)
    {
        $inventory->loadMissing('jabatan');
        $transactionIds = InventoryStockTransaction::withTrashed()
            ->where('inventory_id', $inventory->id)
            ->pluck('id');

        if ($transactionIds->isEmpty()) {
            return;
        }

        InventoryBastDocument::whereIn('inventory_stock_transaction_id', $transactionIds)
            ->get()
            ->each(function (InventoryBastDocument $document) {
                $document->loadMissing('transaction.inventory.jabatan', 'firstParty.Jabatan');
                $senderDivision = $this->resolveSenderDivision($document);

                if ($senderDivision && $document->jabatan_penyerah !== $senderDivision) {
                    $document->forceFill(['jabatan_penyerah' => $senderDivision])->save();
                }

                $this->storePdf($document);
            });
    }

    public function refreshFilesForUser(User $user)
    {
        $documentIds = InventoryBastDocument::query()
            ->where('known_by_user_id', $user->id)
            ->orWhere('first_party_user_id', $user->id)
            ->orWhereHas('transaction', function ($query) use ($user) {
                $query->where('penerima_user_id', $user->id);
            })
            ->pluck('id');

        if ($documentIds->isEmpty()) {
            return;
        }

        InventoryBastDocument::whereIn('id', $documentIds)
            ->get()
            ->each(fn(InventoryBastDocument $document) => $this->storePdf($document));
    }

    public function storePdf(InventoryBastDocument $document)
    {
        $document->loadMissing(
            'transaction.inventory.jabatan',
            'transaction.processedBy',
            'transaction.penerima.Jabatan',
            'signedBy.Jabatan',
            'knownBy.Jabatan',
            'firstParty.Jabatan'
        );

        if (!$document->transaction || !$document->transaction->inventory) {
            return $document;
        }

        $partyDetailsLocked = $this->supportsPartyDetails() && (bool) $document->party_details_locked;
        if (!$partyDetailsLocked) {
            $this->syncPartySnapshots($document);
            $document->refresh();
            $document->loadMissing(
                'transaction.inventory.jabatan',
                'transaction.processedBy',
                'transaction.penerima.Jabatan',
                'signedBy.Jabatan',
                'knownBy.Jabatan',
                'firstParty.Jabatan'
            );
        }

        $senderDivision = $this->resolveSenderDivision($document);
        if (!$partyDetailsLocked && $senderDivision && $document->jabatan_penyerah !== $senderDivision) {
            $updates = ['jabatan_penyerah' => $senderDivision];
            if ($document->firstParty && $document->nama_penyerah !== $document->firstParty->name) {
                $updates['nama_penyerah'] = $document->firstParty->name;
            }

            $document->forceFill($updates)->save();
            $document->refresh();
            $document->loadMissing(
                'transaction.inventory.jabatan',
                'transaction.processedBy',
                'transaction.penerima.Jabatan',
                'signedBy.Jabatan',
                'knownBy.Jabatan',
                'firstParty.Jabatan'
            );
        }

        $pdf = Pdf::loadView('inventory.bast_pdf', [
            'document' => $document,
            'transaction' => $document->transaction,
            'inventory' => $document->transaction->inventory,
            'company' => $this->companyForDocument($document),
        ]);

        $path = self::PDF_DIRECTORY . '/' . $document->id . '.pdf';
        Storage::disk(self::PUBLIC_DISK)->put($path, $pdf->output());
        $document->update(['file_pdf' => $path]);

        return $document->fresh();
    }

    public function storeSignature(InventoryBastDocument $document, $role, $signatureData, User $user, $ip, $userAgent)
    {
        $roleConfig = $this->signatureRoleConfig($role);
        $path = self::SIGNATURE_DIRECTORY . '/' . $document->id . '-' . $this->safeFilename($role) . '-' . time() . '.png';

        Storage::disk(self::PUBLIC_DISK)->put($path, $this->decodeSignature($signatureData));

        $document->forceFill([
            $roleConfig['user_id'] => $user->id,
            $roleConfig['name'] => $user->name,
            $roleConfig['image'] => $path,
            $roleConfig['signed_at'] => now(),
            $roleConfig['ip'] => $ip,
            $roleConfig['user_agent'] => substr((string) $userAgent, 0, self::USER_AGENT_LIMIT),
        ])->save();

        return $this->storePdf($document->fresh());
    }

    private function syncPartySnapshots(InventoryBastDocument $document): void
    {
        $document->loadMissing(
            'transaction.penerima.Jabatan',
            'knownBy.Jabatan',
            'firstParty.Jabatan',
            'transaction.inventory.jabatan'
        );

        $updates = [];

        if ($document->transaction && $document->transaction->penerima) {
            $receiver = $document->transaction->penerima;
            $receiverPosition = $receiver->Jabatan->nama_jabatan ?? null;

            if ($receiver->name && $document->nama_penerima !== $receiver->name) {
                $updates['nama_penerima'] = $receiver->name;
            }

            if ($receiverPosition && $document->jabatan_penerima !== $receiverPosition) {
                $updates['jabatan_penerima'] = $receiverPosition;
            }

            if ($this->supportsPartyDetails()) {
                $receiverDepartment = $document->transaction->departemen_penerima ?: $receiverPosition;
                if ($receiverDepartment && $document->departemen_penerima !== $receiverDepartment) {
                    $updates['departemen_penerima'] = $receiverDepartment;
                }
            }
        }

        if ($document->knownBy && $document->nama_mengetahui !== $document->knownBy->name) {
            $updates['nama_mengetahui'] = $document->knownBy->name;
        }

        if ($document->firstParty) {
            $firstPartyPosition = $this->resolveSenderDivision($document);

            if ($document->nama_penyerah !== $document->firstParty->name) {
                $updates['nama_penyerah'] = $document->firstParty->name;
            }

            if ($firstPartyPosition && $document->jabatan_penyerah !== $firstPartyPosition) {
                $updates['jabatan_penyerah'] = $firstPartyPosition;
            }

            if ($this->supportsPartyDetails() && $firstPartyPosition && $document->departemen_penyerah !== $firstPartyPosition) {
                $updates['departemen_penyerah'] = $firstPartyPosition;
            }
        }

        if (!empty($updates)) {
            $document->forceFill($updates)->save();
        }
    }

    private function signatureRoleConfig($role)
    {
        $roleConfig = InventoryBastDocument::signatureRoles()[$role] ?? null;

        if ($roleConfig) {
            return $roleConfig;
        }

        throw ValidationException::withMessages([
            'signature_data' => 'Role tanda tangan tidak valid.',
        ]);
    }

    private function decodeSignature($signatureData)
    {
        $payload = preg_replace('/^data:image\/png;base64,/', '', (string) $signatureData);
        $binary = base64_decode($payload, true);

        if ($binary !== false && strlen($binary) >= 20) {
            return $binary;
        }

        throw ValidationException::withMessages([
            'signature_data' => 'Tanda tangan tidak valid. Silakan hapus dan tanda tangani ulang.',
        ]);
    }

    private function resolveSenderDivision(InventoryBastDocument $document)
    {
        if ($document->firstParty) {
            return $document->firstParty->Jabatan->nama_jabatan ?? $document->jabatan_penyerah;
        }

        return $document->transaction && $document->transaction->inventory
            ? ($document->transaction->inventory->jabatan->nama_jabatan ?? null)
            : null;
    }

    private function supportsPartyDetails(): bool
    {
        return Schema::hasColumn('inventory_bast_documents', 'departemen_penerima')
            && Schema::hasColumn('inventory_bast_documents', 'departemen_penyerah')
            && Schema::hasColumn('inventory_bast_documents', 'party_details_locked');
    }

    private function generateNumber(Carbon $date, ?int $companyId)
    {
        $romanMonth = $this->romanMonth((int) $date->format('n'));
        $nextNumber = InventoryBastDocument::withoutGlobalScope('company')
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->whereYear('tanggal_surat', $date->year)
            ->whereMonth('tanggal_surat', $date->month)
            ->lockForUpdate()
            ->count() + 1;

        return str_pad($nextNumber, 3, '0', STR_PAD_LEFT) . ' / IT-BAST / ' . $romanMonth . ' / ' . $date->year;
    }

    private function companyForDocument(InventoryBastDocument $document): ?Company
    {
        $companyId = $document->company_id
            ?: optional(optional($document->transaction)->inventory)->company_id
            ?: optional($document->transaction)->company_id;

        return $companyId ? Company::find($companyId) : null;
    }

    private function romanMonth($month)
    {
        $months = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];

        return $months[$month] ?? 'I';
    }

    private function safeFilename($value)
    {
        return trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $value), '-') ?: 'inventory-bast';
    }
}
