<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetKinerja extends Model
{
    use BelongsToCompany, HasFactory;

    protected $guarded = ["id"];

    public function team()
    {
        return $this->hasMany(TargetKinerjaTeam::class, 'target_kinerja_id')->orderBy('id', 'ASC');
    }
}
