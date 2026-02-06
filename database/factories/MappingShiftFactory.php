<?php

namespace Database\Factories;

use App\Models\MappingShift;
use Illuminate\Database\Eloquent\Factories\Factory;

class MappingShiftFactory extends Factory
{
    protected $model = MappingShift::class;

    public function definition()
    {
        return [
            'user_id' => 1,
            'tanggal' => date('Y-m-d'),
            'shift_id' => 1,
            'lock_location' => 0,
            'status_absen' => null,
            'jam_absen' => null,
            'jam_pulang' => null,
            'telat' => null,
            'pulang_cepat' => null,
        ];
    }
}
