<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryStockTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryStockService
{
    private const TRANSAKSI_MASUK = 'masuk';
    private const TRANSAKSI_KELUAR = 'keluar';

    public function stockIn(Inventory $inventory, array $data, User $admin)
    {
        return DB::transaction(function () use ($inventory, $data, $admin) {
            $locked = $this->lockedInventory($inventory);
            $jumlah = $this->normalizeQuantity($data['jumlah']);

            $this->ensureWholeQuantityForCountableStock($locked, $jumlah);

            $stokSebelum = $this->stockBeforeTransaction($locked);
            $stokSesudah = $stokSebelum + $jumlah;

            $this->updateInventoryAfterStockIn($locked, $data, $stokSesudah);

            return InventoryStockTransaction::create(
                $this->stockInTransactionData($locked, $data, $admin, $jumlah, $stokSebelum, $stokSesudah)
            );
        });
    }

    public function stockOut(Inventory $inventory, array $data, User $admin)
    {
        return DB::transaction(function () use ($inventory, $data, $admin) {
            $locked = $this->lockedInventory($inventory);
            $jumlah = $this->normalizeQuantity($data['jumlah']);

            $this->ensureWholeQuantityForCountableStock($locked, $jumlah);

            $stokSebelum = $this->stockBeforeTransaction($locked);
            $this->ensureStockCanBeTaken($stokSebelum, $jumlah);

            $stokSesudah = $stokSebelum - $jumlah;
            $locked->update(['stok' => $stokSesudah]);

            return InventoryStockTransaction::create(
                $this->stockOutTransactionData($locked, $data, $admin, $jumlah, $stokSebelum, $stokSesudah)
            );
        });
    }

    private function lockedInventory(Inventory $inventory): Inventory
    {
        return Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
    }

    private function updateInventoryAfterStockIn(Inventory $inventory, array $data, float $stokSesudah): void
    {
        $inventory->update([
            'stok' => $stokSesudah,
            'kondisi' => ($data['kondisi_barang'] ?? null) ?: $inventory->kondisi,
            'lokasi_id' => ($data['lokasi_id'] ?? null) ?: $inventory->lokasi_id,
        ]);
    }

    private function stockInTransactionData(Inventory $inventory, array $data, User $admin, float $jumlah, float $stokSebelum, float $stokSesudah): array
    {
        return [
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => self::TRANSAKSI_MASUK,
            'jumlah' => $jumlah,
            'stok_sebelum' => $stokSebelum,
            'stok_sesudah' => $stokSesudah,
            'tanggal_transaksi' => $data['tanggal_transaksi'],
            'sumber_barang' => $data['sumber_barang'] ?? null,
            'kondisi_barang' => $data['kondisi_barang'] ?? null,
            'lokasi_id' => $data['lokasi_id'] ?? null,
            'catatan' => $data['catatan'] ?? null,
            'diproses_oleh' => $admin->id,
        ];
    }

    private function stockOutTransactionData(Inventory $inventory, array $data, User $admin, float $jumlah, float $stokSebelum, float $stokSesudah): array
    {
        return [
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => self::TRANSAKSI_KELUAR,
            'jumlah' => $jumlah,
            'stok_sebelum' => $stokSebelum,
            'stok_sesudah' => $stokSesudah,
            'tanggal_transaksi' => $data['tanggal_transaksi'],
            'penerima_user_id' => $data['penerima_user_id'] ?? null,
            'penerima_barang' => $data['penerima_barang'] ?? null,
            'jabatan_penerima' => $data['jabatan_penerima'] ?? null,
            'departemen_penerima' => $data['departemen_penerima'] ?? null,
            'keperluan' => $data['keperluan'] ?? null,
            'kondisi_barang' => $data['kondisi_barang'] ?? null,
            'catatan' => $data['catatan'] ?? null,
            'diproses_oleh' => $admin->id,
        ];
    }

    private function ensureStockCanBeTaken(float $stokSebelum, float $jumlah): void
    {
        if ($stokSebelum <= 0) {
            throw ValidationException::withMessages([
                'jumlah' => 'Stok barang kosong, stok keluar tidak dapat diproses.',
            ]);
        }

        if ($jumlah > $stokSebelum) {
            throw ValidationException::withMessages([
                'jumlah' => 'Jumlah stok keluar tidak boleh melebihi stok tersedia.',
            ]);
        }
    }

    private function normalizeQuantity($value)
    {
        $quantity = round((float) $value, 2);

        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'jumlah' => 'Jumlah harus lebih dari 0.',
            ]);
        }

        return $quantity;
    }

    private function isWholeQuantity(float $quantity): bool
    {
        return abs($quantity - round($quantity)) < 0.000001;
    }

    private function ensureWholeQuantityForCountableStock(Inventory $inventory, float $quantity): void
    {
        if ($inventory->usesWholeStock() && !$this->isWholeQuantity($quantity)) {
            throw ValidationException::withMessages([
                'jumlah' => 'Jumlah untuk satuan ' . $inventory->display_uom . ' harus angka bulat. Barang satuan tidak boleh memakai desimal.',
            ]);
        }
    }

    private function stockBeforeTransaction(Inventory $inventory): float
    {
        if ($inventory->usesWholeStock()) {
            return (float) max(0, round((float) ($inventory->stok ?? 0)));
        }

        return round(max(0, (float) ($inventory->stok ?? 0)), 2);
    }
}
