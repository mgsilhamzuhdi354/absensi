<?php

namespace App\Services;

use App\Models\InventoryBastDocument;
use App\Models\Inventory;
use App\Models\InventoryStockTransaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InventoryBastService
{
    public function createForTransaction(InventoryStockTransaction $transaction, array $data, User $admin)
    {
        if ($transaction->jenis_transaksi !== 'keluar') {
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
            $transaction->loadMissing('inventory.jabatan');
            $inventoryDivision = $transaction->inventory->jabatan->nama_jabatan ?? null;

            $document = InventoryBastDocument::create([
                'inventory_stock_transaction_id' => $transaction->id,
                'nomor_surat' => $this->generateNumber($date),
                'tanggal_surat' => $date->toDateString(),
                'nama_penerima' => $transaction->penerima_barang,
                'jabatan_penerima' => $transaction->jabatan_penerima,
                'nama_penyerah' => $admin->name,
                'jabatan_penyerah' => $inventoryDivision ?: ($admin->Jabatan->nama_jabatan ?? 'IT Engineer'),
                'nama_mengetahui' => $data['nama_mengetahui'] ?? null,
            ]);

            $transaction->loadMissing('inventory.jabatan', 'processedBy');
            $pdf = Pdf::loadView('inventory.bast_pdf', [
                'document' => $document,
                'transaction' => $transaction,
                'inventory' => $transaction->inventory,
            ]);

            $path = 'inventory/bast/' . $document->id . '.pdf';
            Storage::disk('public')->put($path, $pdf->output());
            $document->update(['file_pdf' => $path]);

            return $document->fresh();
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
                $document->loadMissing('transaction.inventory.jabatan');
                $division = $document->transaction && $document->transaction->inventory
                    ? ($document->transaction->inventory->jabatan->nama_jabatan ?? null)
                    : null;

                if ($division) {
                    $document->forceFill([
                        'jabatan_penyerah' => $division,
                    ])->save();
                }

                $this->storePdf($document);
            });
    }

    public function storePdf(InventoryBastDocument $document)
    {
        $document->loadMissing('transaction.inventory.jabatan', 'transaction.processedBy');

        if (!$document->transaction || !$document->transaction->inventory) {
            return $document;
        }

        $division = $document->transaction->inventory->jabatan->nama_jabatan ?? null;
        if ($division && $document->jabatan_penyerah !== $division) {
            $document->forceFill([
                'jabatan_penyerah' => $division,
            ])->save();
            $document->refresh();
            $document->loadMissing('transaction.inventory.jabatan', 'transaction.processedBy');
        }

        $pdf = Pdf::loadView('inventory.bast_pdf', [
            'document' => $document,
            'transaction' => $document->transaction,
            'inventory' => $document->transaction->inventory,
        ]);

        $path = 'inventory/bast/' . $document->id . '.pdf';
        Storage::disk('public')->put($path, $pdf->output());
        $document->update(['file_pdf' => $path]);

        return $document->fresh();
    }

    private function generateNumber(Carbon $date)
    {
        $romanMonth = $this->romanMonth((int) $date->format('n'));
        $nextNumber = InventoryBastDocument::whereYear('tanggal_surat', $date->year)
            ->whereMonth('tanggal_surat', $date->month)
            ->lockForUpdate()
            ->count() + 1;

        return str_pad($nextNumber, 3, '0', STR_PAD_LEFT) . ' / IT-BAST / ' . $romanMonth . ' / ' . $date->year;
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
}
