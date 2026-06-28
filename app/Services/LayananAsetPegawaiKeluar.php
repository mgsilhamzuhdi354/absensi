<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\DokumenPengembalianAset;
use App\Models\InventoryStockTransaction;
use App\Models\InventoryStockVariant;
use App\Models\PegawaiKeluar;
use App\Models\PenyelesaianAsetPegawaiKeluar;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LayananAsetPegawaiKeluar
{
    private const TRANSAKSI_MASUK = 'masuk';
    private const TRANSAKSI_KELUAR = 'keluar';
    private const STATUS_BARANG_TERSEDIA = 'Tersedia';
    private const PUBLIC_DISK = 'public';
    private const PDF_DIRECTORY = 'inventory/return-bast';
    private const SIGNATURE_DIRECTORY = 'inventory/return-bast/signatures';
    private const USER_AGENT_LIMIT = 1000;

    public function syncClearances(PegawaiKeluar $pegawaiKeluar)
    {
        $pegawaiKeluar->loadMissing('user');

        if (!$pegawaiKeluar->user) {
            return collect();
        }

        $this->heldAssetTransactionsForUser($pegawaiKeluar->user_id)
            ->each(function (InventoryStockTransaction $transaction) use ($pegawaiKeluar) {
                $this->createPendingClearance($pegawaiKeluar, $transaction);
            });

        return $this->clearanceQuery($pegawaiKeluar)->get();
    }

    public function pendingClearances(PegawaiKeluar $pegawaiKeluar)
    {
        $this->syncClearances($pegawaiKeluar);

        return $this->clearanceQuery($pegawaiKeluar)
            ->where('status', PenyelesaianAsetPegawaiKeluar::STATUS_PENDING)
            ->get();
    }

    public function heldAssetTransactionsForUser($userId)
    {
        return InventoryStockTransaction::with([
                'inventory.lokasi',
                'inventory.jabatan',
                'penerima.Jabatan',
                'processedBy',
                'bastDocument',
            ])
            ->where('jenis_transaksi', self::TRANSAKSI_KELUAR)
            ->where('penerima_user_id', $userId)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('inventory_stock_transactions as newer')
                    ->whereColumn('newer.inventory_id', 'inventory_stock_transactions.inventory_id')
                    ->whereNull('newer.deleted_at')
                    ->whereRaw("COALESCE(newer.warna_barang, 'Umum') = COALESCE(inventory_stock_transactions.warna_barang, 'Umum')")
                    ->where(function ($latest) {
                        $latest->whereColumn('newer.tanggal_transaksi', '>', 'inventory_stock_transactions.tanggal_transaksi')
                            ->orWhere(function ($sameDate) {
                                $sameDate->whereColumn('newer.tanggal_transaksi', 'inventory_stock_transactions.tanggal_transaksi')
                                    ->whereColumn('newer.id', '>', 'inventory_stock_transactions.id');
                            });
                    });
            })
            ->latest('tanggal_transaksi')
            ->latest('id')
            ->get();
    }

    public function processReturn(PegawaiKeluar $pegawaiKeluar, InventoryStockTransaction $originalTransaction, array $data, User $admin)
    {
        $pegawaiKeluar->loadMissing('user.Jabatan');
        $originalTransaction->loadMissing('inventory', 'penerima.Jabatan');

        $this->ensureTransactionBelongsToExitUser($pegawaiKeluar, $originalTransaction);
        $this->ensureCurrentHolderTransaction($originalTransaction);
        $this->ensureReturnDateIsValid($originalTransaction, $data['tanggal_kembali'] ?? null);

        return DB::transaction(function () use ($pegawaiKeluar, $originalTransaction, $data, $admin) {
            $clearance = $this->lockedClearance($pegawaiKeluar, $originalTransaction);
            $this->ensureClearanceIsPending($clearance);

            $lockedInventory = $this->lockedInventory($originalTransaction);
            $returnData = $this->returnData($lockedInventory, $originalTransaction, $data);

            $this->updateInventoryAfterReturn($lockedInventory, $returnData);
            $this->increaseVariantAfterReturn($lockedInventory, $returnData);
            $returnTransaction = $this->createReturnTransaction($pegawaiKeluar, $originalTransaction, $returnData, $data, $admin);
            $this->markClearanceReturned($clearance, $returnTransaction);

            $document = $this->createReturnDocument($clearance->fresh(), $returnTransaction, $originalTransaction, $data, $admin);

            return [
                'clearance' => $clearance->fresh(),
                'return_transaction' => $returnTransaction->fresh(),
                'document' => $document,
            ];
        });
    }

    public function waive(PegawaiKeluar $pegawaiKeluar, InventoryStockTransaction $originalTransaction, array $data, User $admin)
    {
        $this->ensureTransactionBelongsToExitUser($pegawaiKeluar, $originalTransaction);

        return DB::transaction(function () use ($pegawaiKeluar, $originalTransaction, $data, $admin) {
            $clearance = $this->createPendingClearance($pegawaiKeluar, $originalTransaction);

            if ($clearance->status === PenyelesaianAsetPegawaiKeluar::STATUS_RETURNED) {
                throw ValidationException::withMessages([
                    'asset' => 'Aset ini sudah dikembalikan dan tidak perlu dikecualikan.',
                ]);
            }

            $clearance->forceFill([
                'status' => PenyelesaianAsetPegawaiKeluar::STATUS_WAIVED,
                'waiver_reason' => $data['waiver_reason'],
                'waived_by_user_id' => $admin->id,
                'waived_at' => now(),
            ])->save();

            return $clearance->fresh();
        });
    }

    public function storePdf(DokumenPengembalianAset $document)
    {
        $document->loadMissing($this->returnDocumentRelations());
        $pdf = Pdf::loadView('inventory.pdf_bast_pengembalian', $this->returnDocumentPdfData($document));

        $path = self::PDF_DIRECTORY . '/' . $document->id . '.pdf';
        Storage::disk(self::PUBLIC_DISK)->put($path, $pdf->output());
        $document->update(['file_pdf' => $path]);

        return $document->fresh();
    }

    public function storeSignature(DokumenPengembalianAset $document, $role, $signatureData, User $user, $ip, $userAgent)
    {
        $roleConfig = $this->signatureRoleConfig($role);
        $binary = $this->decodeSignature($signatureData);
        $path = self::SIGNATURE_DIRECTORY . '/' . $document->id . '-' . $this->safeFilename($role) . '-' . time() . '.png';
        Storage::disk(self::PUBLIC_DISK)->put($path, $binary);

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

    private function createPendingClearance(PegawaiKeluar $pegawaiKeluar, InventoryStockTransaction $transaction)
    {
        return PenyelesaianAsetPegawaiKeluar::firstOrCreate([
            'pegawai_keluar_id' => $pegawaiKeluar->id,
            'inventory_stock_transaction_id' => $transaction->id,
        ], [
            'status' => PenyelesaianAsetPegawaiKeluar::STATUS_PENDING,
        ]);
    }

    private function lockedClearance(PegawaiKeluar $pegawaiKeluar, InventoryStockTransaction $transaction)
    {
        $clearance = PenyelesaianAsetPegawaiKeluar::where([
                'pegawai_keluar_id' => $pegawaiKeluar->id,
                'inventory_stock_transaction_id' => $transaction->id,
            ])
            ->lockForUpdate()
            ->first();

        if ($clearance) {
            return $clearance;
        }

        return $this->createPendingClearance($pegawaiKeluar, $transaction);
    }

    private function ensureClearanceIsPending(PenyelesaianAsetPegawaiKeluar $clearance): void
    {
        if ($clearance->status === PenyelesaianAsetPegawaiKeluar::STATUS_PENDING) {
            return;
        }

        throw ValidationException::withMessages([
            'asset' => 'Clearance aset ini sudah selesai diproses.',
        ]);
    }

    private function lockedInventory(InventoryStockTransaction $transaction)
    {
        return Inventory::whereKey($transaction->inventory_id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function returnData(Inventory $inventory, InventoryStockTransaction $originalTransaction, array $data)
    {
        $quantity = $this->normalizeQuantity($originalTransaction->jumlah);
        $stokSebelum = $this->stockBeforeTransaction($inventory);
        $stokSesudah = $stokSebelum + $quantity;

        return [
            'quantity' => $quantity,
            'warna_barang' => $this->normalizeColor($originalTransaction->warna_barang ?? null),
            'stok_sebelum' => $stokSebelum,
            'stok_sesudah' => $stokSesudah,
            'stok_akhir' => $inventory->usesWholeStock() ? (int) round($stokSesudah) : round($stokSesudah, 2),
            'condition' => $data['kondisi_barang'] ?? $inventory->kondisi,
            'status_barang' => $data['status_barang'] ?? ($inventory->status_barang ?: self::STATUS_BARANG_TERSEDIA),
            'lokasi_id' => $data['lokasi_id'] ?? $inventory->lokasi_id,
        ];
    }

    private function updateInventoryAfterReturn(Inventory $inventory, array $returnData): void
    {
        $inventory->update([
            'stok' => $returnData['stok_akhir'],
            'kondisi' => $returnData['condition'],
            'status_barang' => $returnData['status_barang'],
            'lokasi_id' => $returnData['lokasi_id'],
        ]);
    }

    private function increaseVariantAfterReturn(Inventory $inventory, array $returnData): void
    {
        if (!Schema::hasTable('inventory_stock_variants')) {
            return;
        }

        $variant = InventoryStockVariant::where('inventory_id', $inventory->id)
            ->where('warna_barang', $returnData['warna_barang'])
            ->lockForUpdate()
            ->first();

        if (!$variant) {
            $variant = InventoryStockVariant::create([
                'inventory_id' => $inventory->id,
                'warna_barang' => $returnData['warna_barang'],
                'stok' => 0,
            ]);
        }

        $variant->update([
            'stok' => round(max(0, (float) $variant->stok) + (float) $returnData['quantity'], 2),
        ]);
    }

    private function createReturnTransaction(PegawaiKeluar $pegawaiKeluar, InventoryStockTransaction $originalTransaction, array $returnData, array $data, User $admin)
    {
        return InventoryStockTransaction::create([
            'inventory_id' => $originalTransaction->inventory_id,
            'return_for_transaction_id' => $originalTransaction->id,
            'pegawai_keluar_id' => $pegawaiKeluar->id,
            'jenis_transaksi' => self::TRANSAKSI_MASUK,
            'jumlah' => $returnData['quantity'],
            'warna_barang' => $returnData['warna_barang'],
            'stok_sebelum' => $returnData['stok_sebelum'],
            'stok_sesudah' => $returnData['stok_sesudah'],
            'tanggal_transaksi' => $data['tanggal_kembali'],
            'sumber_barang' => 'Pengembalian dari ' . ($pegawaiKeluar->user->name ?? $originalTransaction->penerima_barang ?? 'pegawai'),
            'kondisi_barang' => $returnData['condition'],
            'lokasi_id' => $returnData['lokasi_id'],
            'catatan' => $this->returnTransactionNote($data),
            'diproses_oleh' => $admin->id,
        ]);
    }

    private function markClearanceReturned(PenyelesaianAsetPegawaiKeluar $clearance, InventoryStockTransaction $returnTransaction): void
    {
        $clearance->forceFill([
            'status' => PenyelesaianAsetPegawaiKeluar::STATUS_RETURNED,
            'returned_inventory_stock_transaction_id' => $returnTransaction->id,
            'returned_at' => now(),
        ])->save();
    }

    private function signatureRoleConfig($role)
    {
        $roleConfig = DokumenPengembalianAset::signatureRoles()[$role] ?? null;

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

    private function clearanceQuery(PegawaiKeluar $pegawaiKeluar)
    {
        return PenyelesaianAsetPegawaiKeluar::with([
                'originalTransaction.inventory.lokasi',
                'originalTransaction.inventory.jabatan',
                'originalTransaction.penerima.Jabatan',
                'originalTransaction.processedBy',
                'returnedTransaction',
                'returnDocument.employee',
                'returnDocument.itReceiver',
                'returnDocument.knownBy',
                'waivedBy',
            ])
            ->where('pegawai_keluar_id', $pegawaiKeluar->id)
            ->latest('id');
    }

    private function createReturnDocument(PenyelesaianAsetPegawaiKeluar $clearance, InventoryStockTransaction $returnTransaction, InventoryStockTransaction $originalTransaction, array $data, User $admin)
    {
        $date = Carbon::parse($data['tanggal_kembali'] ?? now());
        $participants = $this->returnDocumentParticipants($clearance, $data, $admin);
        $document = DokumenPengembalianAset::create(
            $this->returnDocumentData($clearance, $returnTransaction, $originalTransaction, $data, $admin, $date, $participants)
        );

        return $this->storePdf($document);
    }

    private function returnDocumentParticipants(PenyelesaianAsetPegawaiKeluar $clearance, array $data, User $admin): array
    {
        $pegawaiKeluar = $clearance->pegawaiKeluar()->with('user.Jabatan')->first();

        return [
            'pegawai_keluar' => $pegawaiKeluar,
            'employee' => $pegawaiKeluar ? $pegawaiKeluar->user : null,
            'it_receiver' => !empty($data['it_receiver_user_id'])
                ? User::with('Jabatan')->find($data['it_receiver_user_id'])
                : $admin->loadMissing('Jabatan'),
            'known_by' => !empty($data['known_by_user_id'])
                ? User::with('Jabatan')->find($data['known_by_user_id'])
                : null,
        ];
    }

    private function returnDocumentData(
        PenyelesaianAsetPegawaiKeluar $clearance,
        InventoryStockTransaction $returnTransaction,
        InventoryStockTransaction $originalTransaction,
        array $data,
        User $admin,
        Carbon $date,
        array $participants
    ): array {
        $pegawaiKeluar = $participants['pegawai_keluar'];
        $employee = $participants['employee'];
        $itReceiver = $participants['it_receiver'];
        $knownBy = $participants['known_by'];
        $employeePosition = optional(optional($employee)->Jabatan)->nama_jabatan;
        $receiverPosition = optional(optional($itReceiver)->Jabatan)->nama_jabatan;

        return [
            'pegawai_keluar_asset_clearance_id' => $clearance->id,
            'return_inventory_stock_transaction_id' => $returnTransaction->id,
            'original_inventory_stock_transaction_id' => $originalTransaction->id,
            'pegawai_keluar_id' => $pegawaiKeluar->id,
            'inventory_id' => $originalTransaction->inventory_id,
            'nomor_surat' => $this->generateReturnNumber($date),
            'tanggal_surat' => $date->toDateString(),
            'employee_user_id' => $employee->id ?? null,
            'it_receiver_user_id' => $itReceiver->id ?? null,
            'known_by_user_id' => $knownBy->id ?? null,
            'nama_pengembali' => $employee->name ?? $originalTransaction->penerima_barang,
            'jabatan_pengembali' => $employeePosition ?: $originalTransaction->jabatan_penerima,
            'departemen_pengembali' => $employeePosition ?: $originalTransaction->departemen_penerima,
            'nama_penerima' => $itReceiver->name ?? $admin->name,
            'jabatan_penerima' => $receiverPosition ?: 'IT',
            'departemen_penerima' => $receiverPosition ?: 'IT',
            'nama_mengetahui' => $knownBy->name ?? null,
            'kondisi_kembali' => $data['kondisi_barang'] ?? null,
            'kelengkapan' => $data['kelengkapan'] ?? null,
            'catatan' => $data['catatan'] ?? null,
        ];
    }

    private function returnDocumentRelations(): array
    {
        return [
            'inventory.lokasi',
            'inventory.jabatan',
            'pegawaiKeluar.user.Jabatan',
            'employee.Jabatan',
            'itReceiver.Jabatan',
            'knownBy.Jabatan',
            'originalTransaction',
            'returnTransaction',
        ];
    }

    private function returnDocumentPdfData(DokumenPengembalianAset $document): array
    {
        return [
            'document' => $document,
            'inventory' => $document->inventory,
            'pegawaiKeluar' => $document->pegawaiKeluar,
            'originalTransaction' => $document->originalTransaction,
            'returnTransaction' => $document->returnTransaction,
        ];
    }

    private function ensureTransactionBelongsToExitUser(PegawaiKeluar $pegawaiKeluar, InventoryStockTransaction $transaction): void
    {
        if ((int) $transaction->penerima_user_id !== (int) $pegawaiKeluar->user_id) {
            throw ValidationException::withMessages([
                'asset' => 'Aset ini tidak tercatat sebagai aset pegawai yang keluar.',
            ]);
        }

        if ($transaction->jenis_transaksi !== self::TRANSAKSI_KELUAR) {
            throw ValidationException::withMessages([
                'asset' => 'Hanya transaksi aset keluar yang bisa diproses sebagai pengembalian.',
            ]);
        }
    }

    private function ensureCurrentHolderTransaction(InventoryStockTransaction $transaction): void
    {
        $latestTransactionId = InventoryStockTransaction::where('inventory_id', $transaction->inventory_id)
            ->latest('tanggal_transaksi')
            ->latest('id')
            ->value('id');

        if ((int) $latestTransactionId !== (int) $transaction->id) {
            throw ValidationException::withMessages([
                'asset' => 'Aset ini sudah tidak tercatat sebagai aset yang masih dipegang pegawai tersebut.',
            ]);
        }
    }

    private function ensureReturnDateIsValid(InventoryStockTransaction $transaction, $returnDate): void
    {
        if (!$transaction->tanggal_transaksi || !$returnDate) {
            return;
        }

        if (Carbon::parse($returnDate)->lt(Carbon::parse($transaction->tanggal_transaksi))) {
            throw ValidationException::withMessages([
                'tanggal_kembali' => 'Tanggal pengembalian tidak boleh lebih awal dari tanggal serah terima aset.',
            ]);
        }
    }

    private function returnTransactionNote(array $data)
    {
        $notes = ['Pengembalian aset dari proses pegawai keluar.'];

        if (!empty($data['kelengkapan'])) {
            $notes[] = 'Kelengkapan: ' . $data['kelengkapan'];
        }

        if (!empty($data['catatan'])) {
            $notes[] = 'Catatan: ' . $data['catatan'];
        }

        return implode(' ', $notes);
    }

    private function normalizeQuantity($value)
    {
        $quantity = round((float) $value, 2);

        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'jumlah' => 'Jumlah pengembalian harus lebih dari 0.',
            ]);
        }

        return $quantity;
    }

    private function normalizeColor($value): string
    {
        $color = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $color !== '' ? mb_substr($color, 0, 80) : 'Umum';
    }

    private function stockBeforeTransaction(Inventory $inventory): float
    {
        if ($inventory->usesWholeStock()) {
            return (float) max(0, round((float) ($inventory->stok ?? 0)));
        }

        return round(max(0, (float) ($inventory->stok ?? 0)), 2);
    }

    private function generateReturnNumber(Carbon $date)
    {
        $romanMonth = $this->romanMonth((int) $date->format('n'));
        $nextNumber = DokumenPengembalianAset::whereYear('tanggal_surat', $date->year)
            ->whereMonth('tanggal_surat', $date->month)
            ->lockForUpdate()
            ->count() + 1;

        return str_pad($nextNumber, 3, '0', STR_PAD_LEFT) . ' / IT-BAST-PB / ' . $romanMonth . ' / ' . $date->year;
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
        return trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $value), '-') ?: 'inventory-return';
    }
}
