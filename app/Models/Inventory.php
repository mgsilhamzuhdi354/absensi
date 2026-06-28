<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;
    protected $guarded = ["id"];

    protected $casts = [
        'stok' => 'float',
        'tanggal_masuk' => 'date',
        'stock_alert_enabled' => 'boolean',
    ];

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    public function stockTransactions()
    {
        return $this->hasMany(InventoryStockTransaction::class)->latest('tanggal_transaksi')->latest('id');
    }

    public function stockVariants()
    {
        return $this->hasMany(InventoryStockVariant::class)->orderBy('warna_barang');
    }

    public function getStockQuantityAttribute(): float
    {
        $stock = (float) ($this->stok ?? 0);

        if ($this->usesWholeStock()) {
            return (float) max(0, round($stock));
        }

        return round(max(0, $stock), 2);
    }

    public function getFormattedStockAttribute(): string
    {
        return $this->formatStockValue($this->stock_quantity);
    }

    public function formatStockValue($value): string
    {
        if ($this->usesWholeStock()) {
            return (string) (int) round((float) $value);
        }

        $formatted = number_format((float) $value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    public function getDisplayUomAttribute(): string
    {
        $uom = trim((string) ($this->uom ?? ''));

        return $uom !== '' ? $uom : 'Unit';
    }

    public function usesWholeStock(): bool
    {
        return self::isWholeStockUom($this->uom);
    }

    public static function isWholeStockUom($uom): bool
    {
        $normalized = strtolower(trim((string) $uom));
        $normalized = preg_replace('/[^a-z0-9]+/', '', $normalized) ?? '';

        return in_array($normalized, [
            'unit',
            'pcs',
            'pc',
            'piece',
            'pieces',
            'set',
            'box',
            'pack',
            'buah',
        ], true);
    }
}
