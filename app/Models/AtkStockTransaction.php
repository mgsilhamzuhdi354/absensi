<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AtkStockTransaction extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'jumlah' => 'float',
        'stok_sebelum' => 'float',
        'stok_sesudah' => 'float',
        'deleted_at' => 'datetime',
    ];

    public function atk()
    {
        return $this->belongsTo(Atk::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
