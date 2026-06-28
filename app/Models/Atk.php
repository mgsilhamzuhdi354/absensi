<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Atk extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'stok' => 'float',
        'active' => 'integer',
        'stock_alert_enabled' => 'boolean',
    ];

    public function getFormattedStockAttribute(): string
    {
        $formatted = number_format((float) ($this->stok ?? 0), 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    public function stockTransactions()
    {
        return $this->hasMany(AtkStockTransaction::class)->latest('tanggal_transaksi')->latest('id');
    }

    public function stockVariants()
    {
        return $this->hasMany(AtkStockVariant::class)->orderBy('warna_barang');
    }

    public function formatStockValue($value): string
    {
        $formatted = number_format((float) ($value ?? 0), 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}
