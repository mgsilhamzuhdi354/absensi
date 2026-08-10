<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use BelongsToCompany, HasFactory;
    protected $guarded =["id"];

    public function User()
    {
        return $this->belongsTo(User::class);
    }
}
