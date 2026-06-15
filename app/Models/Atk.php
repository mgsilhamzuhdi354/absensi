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
    ];

    public function getFormattedStockAttribute(): string
    {
        $formatted = number_format((float) ($this->stok ?? 0), 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}
