<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeCompanyTransfer extends Model
{
    use HasFactory, BelongsToCompany;

    protected $guarded = ['id'];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sourceCompany()
    {
        return $this->belongsTo(Company::class, 'source_company_id');
    }

    public function destinationCompany()
    {
        return $this->belongsTo(Company::class, 'destination_company_id');
    }

    public function sourceJabatan()
    {
        return $this->belongsTo(Jabatan::class, 'source_jabatan_id');
    }

    public function destinationJabatan()
    {
        return $this->belongsTo(Jabatan::class, 'destination_jabatan_id');
    }

    public function sourceLokasi()
    {
        return $this->belongsTo(Lokasi::class, 'source_lokasi_id');
    }

    public function destinationLokasi()
    {
        return $this->belongsTo(Lokasi::class, 'destination_lokasi_id');
    }

    public function transferredBy()
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
