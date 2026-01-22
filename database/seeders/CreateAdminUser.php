<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Seeder
{
    public function run()
    {
        // Hapus user lama jika ada (opsional, tapi biar bersih)
        DB::table('users')->where('email', 'admin@gmail.com')->delete();

        DB::table('users')->insert([
            'name' => 'Super Admin',
            'email' => 'admin@gmail.com',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'is_admin' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
            // Field lain yang not null di database user:
            'izin_cuti' => 12,
            'izin_lainnya' => 0,
            'izin_telat' => 0,
            'izin_pulang_cepat' => 0,
        ]);
        
        $this->command->info('User admin berhasil dibuat! Login: admin@gmail.com / password');
    }
}
