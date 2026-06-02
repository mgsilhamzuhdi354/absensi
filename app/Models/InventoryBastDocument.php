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
        'signed_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(InventoryStockTransaction::class, 'inventory_stock_transaction_id')->withTrashed();
    }

    public function signedBy()
    {
        return $this->belongsTo(User::class, 'signed_by_user_id');
    }

    public function getIsSignedAttribute()
    {
        return $this->signed_at !== null;
    }
}
