<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rapat extends Model
{
    use BelongsToCompany, HasFactory;
    protected $guarded = ["id"];

    public function pegawai()
    {
        return $this->hasMany(RapatPegawai::class, 'rapat_id');
    }

    public function notulen()
    {
        return $this->hasMany(RapatNotulen::class, 'rapat_id');
    }
}
