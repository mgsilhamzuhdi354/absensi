<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtkStockVariant extends Model
{
    use BelongsToCompany, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'stok' => 'float',
    ];

    public function atk()
    {
        return $this->belongsTo(Atk::class);
    }

    public function getFormattedStockAttribute(): string
    {
        $formatted = number_format((float) ($this->stok ?? 0), 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}
