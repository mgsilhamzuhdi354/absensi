<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanKeuanganItem extends Model
{
    use BelongsToCompany, HasFactory;
    protected $guarded = ["id"];

    public function pk()
    {
        return $this->belongsTo(PengajuanKeuangan::class, 'pengajuan_keuangan_id');
    }
}
