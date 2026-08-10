<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStockVariant extends Model
{
    use BelongsToCompany, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'stok' => 'float',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function getFormattedStockAttribute(): string
    {
        $formatted = number_format((float) ($this->stok ?? 0), 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}
