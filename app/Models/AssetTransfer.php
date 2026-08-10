<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetTransfer extends Model
{
    use BelongsToCompany, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'jumlah' => 'float',
        'tanggal_transfer' => 'date',
    ];

    public function sourceCompany()
    {
        return $this->belongsTo(Company::class, 'source_company_id');
    }

    public function destinationCompany()
    {
        return $this->belongsTo(Company::class, 'destination_company_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }
}
