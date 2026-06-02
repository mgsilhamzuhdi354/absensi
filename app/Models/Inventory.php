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
}
