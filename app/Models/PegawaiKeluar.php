<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PegawaiKeluar extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $guarded = ["id"];

    public static function approvalStatuses()
    {
        return [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function assetClearances()
    {
        return $this->hasMany(PenyelesaianAsetPegawaiKeluar::class, 'pegawai_keluar_id');
    }
}
