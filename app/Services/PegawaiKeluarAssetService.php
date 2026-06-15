<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryReturnDocument;
use App\Models\InventoryStockTransaction;
use App\Models\PegawaiKeluar;
use App\Models\PegawaiKeluarAssetClearance;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PegawaiKeluarAssetService
{
    public function syncClearances(PegawaiKeluar $pegawaiKeluar)
    {
        $pegawaiKeluar->loadMissing('user');

        if (!$pegawaiKeluar->user) {
            return collect();
        }

        $this->heldAssetTransactionsForUser($pegawaiKeluar->user_id)
            ->each(function (InventoryStockTransaction $transaction) use ($pegawaiKeluar) {
                PegawaiKeluarAssetClearance::firstOrCreate([
                    'pegawai_keluar_id' => $pegawaiKeluar->id,
                    'inventory_stock_transaction_id' => $transaction->id,
                ], [
                    'status' => PegawaiKeluarAssetClearance::STATUS_PENDING,
                ]);
            });

        return $this->clearanceQuery($pegawaiKeluar)->get();
    }

    public function pendingClearances(PegawaiKeluar $pegawaiKeluar)
    {
        $this->syncClearances($pegawaiKeluar);

        return $this->clearanceQuery($pegawaiKeluar)
            ->where('status', PegawaiKeluarAssetClearance::STATUS_PENDING)
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
            ->where('jenis_transaksi', 'keluar')
            ->where('penerima_user_id', $userId)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('inventory_stock_transactions as newer')
                    ->whereColumn('newer.inventory_id', 'inventory_stock_transactions.inventory_id')
                    ->whereNull('newer.deleted_at')
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
            $clearance = PegawaiKeluarAssetClearance::where([
                    'pegawai_keluar_id' => $pegawaiKeluar->id,
                    'inventory_stock_transaction_id' => $originalTransaction->id,
                ])
                ->lockForUpdate()
                ->first();

            if (!$clearance) {
                $clearance = PegawaiKeluarAssetClearance::create([
                    'pegawai_keluar_id' => $pegawaiKeluar->id,
                    'inventory_stock_transaction_id' => $originalTransaction->id,
                    'status' => PegawaiKeluarAssetClearance::STATUS_PENDING,
                ]);
            }

            if ($clearance->status !== PegawaiKeluarAssetClearance::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'asset' => 'Clearance aset ini sudah selesai diproses.',
                ]);
            }

            $lockedInventory = Inventory::whereKey($originalTransaction->inventory_id)->lockForUpdate()->firstOrFail();
            $quantity = $this->normalizeQuantity($originalTransaction->jumlah);
            $stokSebelum = $this->stockBeforeTransaction($lockedInventory);
            $stokSesudah = $stokSebelum + $quantity;
            $condition = $data['kondisi_barang'] ?? $lockedInventory->kondisi;
            $statusBarang = $data['status_barang'] ?? ($lockedInventory->status_barang ?: 'Tersedia');
            $lokasiId = $data['lokasi_id'] ?? $lockedInventory->lokasi_id;

            $lockedInventory->update([
                'stok' => $lockedInventory->usesWholeStock() ? (int) round($stokSesudah) : round($stokSesudah, 2),
                'kondisi' => $condition,
                'status_barang' => $statusBarang,
                'lokasi_id' => $lokasiId,
            ]);

            $returnTransaction = InventoryStockTransaction::create([
                'inventory_id' => $lockedInventory->id,
                'return_for_transaction_id' => $originalTransaction->id,
                'pegawai_keluar_id' => $pegawaiKeluar->id,
                'jenis_transaksi' => 'masuk',
                'jumlah' => $quantity,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokSesudah,
                'tanggal_transaksi' => $data['tanggal_kembali'],
                'sumber_barang' => 'Pengembalian dari ' . ($pegawaiKeluar->user->name ?? $originalTransaction->penerima_barang ?? 'pegawai'),
                'kondisi_barang' => $condition,
                'lokasi_id' => $lokasiId,
                'catatan' => $this->returnTransactionNote($data),
                'diproses_oleh' => $admin->id,
            ]);

            $clearance->forceFill([
                'status' => PegawaiKeluarAssetClearance::STATUS_RETURNED,
                'returned_inventory_stock_transaction_id' => $returnTransaction->id,
                'returned_at' => now(),
            ])->save();

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
            $clearance = PegawaiKeluarAssetClearance::firstOrCreate([
                'pegawai_keluar_id' => $pegawaiKeluar->id,
                'inventory_stock_transaction_id' => $originalTransaction->id,
            ], [
                'status' => PegawaiKeluarAssetClearance::STATUS_PENDING,
            ]);

            if ($clearance->status === PegawaiKeluarAssetClearance::STATUS_RETURNED) {
                throw ValidationException::withMessages([
                    'asset' => 'Aset ini sudah dikembalikan dan tidak perlu dikecualikan.',
                ]);
            }

            $clearance->forceFill([
                'status' => PegawaiKeluarAssetClearance::STATUS_WAIVED,
                'waiver_reason' => $data['waiver_reason'],
                'waived_by_user_id' => $admin->id,
                'waived_at' => now(),
            ])->save();

            return $clearance->fresh();
        });
    }

    public function storePdf(InventoryReturnDocument $document)
    {
        $document->loadMissing(
            'inventory.lokasi',
            'inventory.jabatan',
            'pegawaiKeluar.user.Jabatan',
            'employee.Jabatan',
            'itReceiver.Jabatan',
            'knownBy.Jabatan',
            'originalTransaction',
            'returnTransaction'
        );

        $pdf = Pdf::loadView('inventory.return_bast_pdf', [
            'document' => $document,
            'inventory' => $document->inventory,
            'pegawaiKeluar' => $document->pegawaiKeluar,
            'originalTransaction' => $document->originalTransaction,
            'returnTransaction' => $document->returnTransaction,
        ]);

        $path = 'inventory/return-bast/' . $document->id . '.pdf';
        Storage::disk('public')->put($path, $pdf->output());
        $document->update(['file_pdf' => $path]);

        return $document->fresh();
    }

    public function storeSignature(InventoryReturnDocument $document, $role, $signatureData, User $user, $ip, $userAgent)
    {
        $roleConfig = InventoryReturnDocument::signatureRoles()[$role] ?? null;
        if (!$roleConfig) {
            throw ValidationException::withMessages([
                'signature_data' => 'Role tanda tangan tidak valid.',
            ]);
        }

        $payload = preg_replace('/^data:image\/png;base64,/', '', (string) $signatureData);
        $binary = base64_decode($payload, true);

        if ($binary === false || strlen($binary) < 20) {
            throw ValidationException::withMessages([
                'signature_data' => 'Tanda tangan tidak valid. Silakan hapus dan tanda tangani ulang.',
            ]);
        }

        $path = 'inventory/return-bast/signatures/' . $document->id . '-' . $this->safeFilename($role) . '-' . time() . '.png';
        Storage::disk('public')->put($path, $binary);

        $document->forceFill([
            $roleConfig['user_id'] => $user->id,
            $roleConfig['name'] => $user->name,
            $roleConfig['image'] => $path,
            $roleConfig['signed_at'] => now(),
            $roleConfig['ip'] => $ip,
            $roleConfig['user_agent'] => substr((string) $userAgent, 0, 1000),
        ])->save();

        return $this->storePdf($document->fresh());
    }

    private function clearanceQuery(PegawaiKeluar $pegawaiKeluar)
    {
        return PegawaiKeluarAssetClearance::with([
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

    private function createReturnDocument(PegawaiKeluarAssetClearance $clearance, InventoryStockTransaction $returnTransaction, InventoryStockTransaction $originalTransaction, array $data, User $admin)
    {
        $date = Carbon::parse($data['tanggal_kembali'] ?? now());
        $pegawaiKeluar = $clearance->pegawaiKeluar()->with('user.Jabatan')->first();
        $employee = $pegawaiKeluar ? $pegawaiKeluar->user : null;
        $itReceiver = !empty($data['it_receiver_user_id'])
            ? User::with('Jabatan')->find($data['it_receiver_user_id'])
            : $admin->loadMissing('Jabatan');
        $knownBy = !empty($data['known_by_user_id'])
            ? User::with('Jabatan')->find($data['known_by_user_id'])
            : null;

        $document = InventoryReturnDocument::create([
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
            'jabatan_pengembali' => optional(optional($employee)->Jabatan)->nama_jabatan ?: $originalTransaction->jabatan_penerima,
            'departemen_pengembali' => optional(optional($employee)->Jabatan)->nama_jabatan ?: $originalTransaction->departemen_penerima,
            'nama_penerima' => $itReceiver->name ?? $admin->name,
            'jabatan_penerima' => optional(optional($itReceiver)->Jabatan)->nama_jabatan ?: 'IT',
            'departemen_penerima' => optional(optional($itReceiver)->Jabatan)->nama_jabatan ?: 'IT',
            'nama_mengetahui' => $knownBy->name ?? null,
            'kondisi_kembali' => $data['kondisi_barang'] ?? null,
            'kelengkapan' => $data['kelengkapan'] ?? null,
            'catatan' => $data['catatan'] ?? null,
        ]);

        return $this->storePdf($document);
    }

    private function ensureTransactionBelongsToExitUser(PegawaiKeluar $pegawaiKeluar, InventoryStockTransaction $transaction): void
    {
        if ((int) $transaction->penerima_user_id !== (int) $pegawaiKeluar->user_id) {
            throw ValidationException::withMessages([
                'asset' => 'Aset ini tidak tercatat sebagai aset pegawai yang keluar.',
            ]);
        }

        if ($transaction->jenis_transaksi !== 'keluar') {
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
        $nextNumber = InventoryReturnDocument::whereYear('tanggal_surat', $date->year)
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
