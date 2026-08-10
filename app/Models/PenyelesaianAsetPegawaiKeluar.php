<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenyelesaianAsetPegawaiKeluar extends Model
{
    use BelongsToCompany, HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_WAIVED = 'waived';

    protected $table = 'pegawai_keluar_asset_clearances';

    protected $guarded = ['id'];

    protected $casts = [
        'returned_at' => 'datetime',
        'waived_at' => 'datetime',
    ];

    public function pegawaiKeluar()
    {
        return $this->belongsTo(PegawaiKeluar::class);
    }

    public function originalTransaction()
    {
        return $this->belongsTo(InventoryStockTransaction::class, 'inventory_stock_transaction_id')->withTrashed();
    }

    public function returnedTransaction()
    {
        return $this->belongsTo(InventoryStockTransaction::class, 'returned_inventory_stock_transaction_id')->withTrashed();
    }

    public function returnDocument()
    {
        return $this->hasOne(DokumenPengembalianAset::class, 'pegawai_keluar_asset_clearance_id');
    }

    public function waivedBy()
    {
        return $this->belongsTo(User::class, 'waived_by_user_id');
    }

    public function getStatusLabelAttribute()
    {
        if ($this->status === self::STATUS_RETURNED) {
            return 'Dikembalikan';
        }

        if ($this->status === self::STATUS_WAIVED) {
            return 'Dikecualikan';
        }

        return 'Belum Kembali';
    }
}
