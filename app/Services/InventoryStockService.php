<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryStockTransaction;
use App\Models\InventoryStockVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            $color = $this->normalizeColor($data['warna_barang'] ?? null);
            $this->ensureVariantsInitialized($locked);
            $this->increaseVariantStock($locked, $color, $jumlah);

            $this->updateInventoryAfterStockIn($locked, $data, $stokSesudah);

            return InventoryStockTransaction::create(
                $this->stockInTransactionData($locked, $data, $admin, $jumlah, $stokSebelum, $stokSesudah, $color)
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
            $color = $this->normalizeColor($data['warna_barang'] ?? null);
            $this->ensureVariantsInitialized($locked);
            $this->decreaseVariantStock($locked, $color, $jumlah);

            $stokSesudah = $stokSebelum - $jumlah;
            $locked->update(['stok' => $stokSesudah]);

            return InventoryStockTransaction::create(
                $this->stockOutTransactionData($locked, $data, $admin, $jumlah, $stokSebelum, $stokSesudah, $color)
            );
        });
    }

    public function syncVariantTotal(Inventory $inventory, ?string $color): void
    {
        if (!Schema::hasTable('inventory_stock_variants')) {
            return;
        }

        DB::transaction(function () use ($inventory, $color) {
            $locked = $this->lockedInventory($inventory);
            $targetStock = $this->stockBeforeTransaction($locked);
            $variantTotal = round((float) InventoryStockVariant::where('inventory_id', $locked->id)->sum('stok'), 2);
            $delta = round($targetStock - $variantTotal, 2);

            $this->applyVariantDelta($locked, $this->normalizeColor($color), $delta);
        });
    }

    public function reverseVariantForDeletedTransaction(Inventory $inventory, InventoryStockTransaction $transaction): void
    {
        if (!Schema::hasTable('inventory_stock_variants')) {
            return;
        }

        $quantity = $this->normalizeQuantity($transaction->jumlah);
        $color = $this->normalizeColor($transaction->warna_barang ?? null);
        $this->ensureVariantsInitialized($inventory);

        if ($transaction->jenis_transaksi === self::TRANSAKSI_KELUAR) {
            $this->increaseVariantStock($inventory, $color, $quantity);
            return;
        }

        $this->decreaseVariantStock($inventory, $color, $quantity, 'Transaksi stok masuk warna ' . $color . ' belum bisa dihapus karena stok warna saat ini tidak cukup untuk dibalik.');
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

    private function stockInTransactionData(Inventory $inventory, array $data, User $admin, float $jumlah, float $stokSebelum, float $stokSesudah, string $color): array
    {
        return [
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => self::TRANSAKSI_MASUK,
            'jumlah' => $jumlah,
            'warna_barang' => $color,
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

    private function stockOutTransactionData(Inventory $inventory, array $data, User $admin, float $jumlah, float $stokSebelum, float $stokSesudah, string $color): array
    {
        return [
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => self::TRANSAKSI_KELUAR,
            'jumlah' => $jumlah,
            'warna_barang' => $color,
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

    private function increaseVariantStock(Inventory $inventory, string $color, float $quantity): void
    {
        if (!Schema::hasTable('inventory_stock_variants')) {
            return;
        }

        $variant = $this->lockOrCreateVariant($inventory, $color);
        $variant->update([
            'stok' => round(max(0, (float) $variant->stok) + $quantity, 2),
        ]);
    }

    private function decreaseVariantStock(Inventory $inventory, string $color, float $quantity, ?string $message = null): void
    {
        if (!Schema::hasTable('inventory_stock_variants')) {
            return;
        }

        $variant = $this->lockOrCreateVariant($inventory, $color);
        $stockBefore = round(max(0, (float) ($variant->stok ?? 0)), 2);
        $stockAfter = $stockBefore - $quantity;

        if ($stockAfter < -0.000001) {
            throw ValidationException::withMessages([
                'warna_barang' => $message ?: 'Stok warna ' . $color . ' tidak mencukupi. Stok tersedia ' . $this->formatStock($stockBefore) . ' ' . $inventory->display_uom . '.',
            ]);
        }

        $variant->update([
            'stok' => round(max(0, $stockAfter), 2),
        ]);
    }

    private function applyVariantDelta(Inventory $inventory, string $color, float $delta): void
    {
        if (abs($delta) < 0.000001) {
            return;
        }

        if ($delta > 0) {
            $this->increaseVariantStock($inventory, $color, $delta);
            return;
        }

        $remaining = abs($delta);
        $variants = InventoryStockVariant::where('inventory_id', $inventory->id)
            ->orderByRaw('CASE WHEN warna_barang = ? THEN 0 ELSE 1 END', [$color])
            ->orderByDesc('stok')
            ->lockForUpdate()
            ->get();

        foreach ($variants as $variant) {
            if ($remaining <= 0) {
                break;
            }

            $available = round(max(0, (float) $variant->stok), 2);
            $taken = min($available, $remaining);
            $variant->update([
                'stok' => round($available - $taken, 2),
            ]);
            $remaining = round($remaining - $taken, 2);
        }
    }

    private function lockOrCreateVariant(Inventory $inventory, string $color): InventoryStockVariant
    {
        $variant = InventoryStockVariant::where('inventory_id', $inventory->id)
            ->where('warna_barang', $color)
            ->lockForUpdate()
            ->first();

        if ($variant) {
            return $variant;
        }

        return InventoryStockVariant::create([
            'inventory_id' => $inventory->id,
            'warna_barang' => $color,
            'stok' => 0,
        ]);
    }

    private function ensureVariantsInitialized(Inventory $inventory): void
    {
        if (!Schema::hasTable('inventory_stock_variants')) {
            return;
        }

        $hasVariant = InventoryStockVariant::where('inventory_id', $inventory->id)->exists();
        if ($hasVariant) {
            return;
        }

        $stock = $this->stockBeforeTransaction($inventory);
        if ($stock <= 0) {
            return;
        }

        InventoryStockVariant::create([
            'inventory_id' => $inventory->id,
            'warna_barang' => 'Umum',
            'stok' => $stock,
        ]);
    }

    private function normalizeColor($value): string
    {
        $color = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $color !== '' ? mb_substr($color, 0, 80) : 'Umum';
    }

    private function formatStock(float $stock): string
    {
        $formatted = number_format($stock, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}
