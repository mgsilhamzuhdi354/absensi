<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResetCuti extends Model
{
    use BelongsToCompany, HasFactory;
    protected $guarded = ['id'];
}
