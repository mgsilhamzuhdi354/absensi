<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryStockTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'jumlah' => 'float',
        'stok_sebelum' => 'float',
        'stok_sesudah' => 'float',
        'deleted_at' => 'datetime',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    public function penerima()
    {
        return $this->belongsTo(User::class, 'penerima_user_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function bastDocument()
    {
        return $this->hasOne(InventoryBastDocument::class, 'inventory_stock_transaction_id');
    }

    public function returnDocument()
    {
        return $this->hasOne(InventoryReturnDocument::class, 'return_inventory_stock_transaction_id');
    }

    public function returnedFromTransaction()
    {
        return $this->belongsTo(self::class, 'return_for_transaction_id')->withTrashed();
    }

    public function returnedTransaction()
    {
        return $this->hasOne(self::class, 'return_for_transaction_id')->withTrashed();
    }

    public function pegawaiKeluar()
    {
        return $this->belongsTo(PegawaiKeluar::class);
    }
}
