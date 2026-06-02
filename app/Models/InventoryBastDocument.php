<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryBastDocument extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_surat' => 'date',
    ];

    public function transaction()
    {
        return $this->belongsTo(InventoryStockTransaction::class, 'inventory_stock_transaction_id')->withTrashed();
    }
}
