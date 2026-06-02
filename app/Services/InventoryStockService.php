<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryStockTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryStockService
{
    public function stockIn(Inventory $inventory, array $data, User $admin)
    {
        return DB::transaction(function () use ($inventory, $data, $admin) {
            $locked = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $jumlah = $this->normalizeQuantity($data['jumlah']);
            $stokSebelum = (float) ($locked->stok ?? 0);
            $stokSesudah = $stokSebelum + $jumlah;

            $locked->update([
                'stok' => $stokSesudah,
                'kondisi' => ($data['kondisi_barang'] ?? null) ?: $locked->kondisi,
                'lokasi_id' => ($data['lokasi_id'] ?? null) ?: $locked->lokasi_id,
            ]);

            return InventoryStockTransaction::create([
                'inventory_id' => $locked->id,
                'jenis_transaksi' => 'masuk',
                'jumlah' => $jumlah,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokSesudah,
                'tanggal_transaksi' => $data['tanggal_transaksi'],
                'sumber_barang' => $data['sumber_barang'] ?? null,
                'kondisi_barang' => $data['kondisi_barang'] ?? null,
                'lokasi_id' => $data['lokasi_id'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'diproses_oleh' => $admin->id,
            ]);
        });
    }

    public function stockOut(Inventory $inventory, array $data, User $admin)
    {
        return DB::transaction(function () use ($inventory, $data, $admin) {
            $locked = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $jumlah = $this->normalizeQuantity($data['jumlah']);
            $stokSebelum = (float) ($locked->stok ?? 0);

            if (!$this->isWholeQuantity($jumlah)) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Jumlah pindah tangan harus angka bulat. Barang seperti laptop tidak boleh dikurangi sebagian.',
                ]);
            }

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

            $stokSesudah = $stokSebelum - $jumlah;
            $locked->update(['stok' => $stokSesudah]);

            return InventoryStockTransaction::create([
                'inventory_id' => $locked->id,
                'jenis_transaksi' => 'keluar',
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
            ]);
        });
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
}
