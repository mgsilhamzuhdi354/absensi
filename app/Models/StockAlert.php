<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAlert extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'stock' => 'float',
        'threshold' => 'float',
        'last_notified_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function alertable()
    {
        return $this->morphTo();
    }
}
