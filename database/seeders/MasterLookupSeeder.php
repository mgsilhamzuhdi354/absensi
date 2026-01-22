<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterLookup;

class MasterLookupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            // Jenis Keberhentian (Exit Types)
            ['type' => 'exit_type', 'name' => 'PHK', 'value' => 'PHK', 'sort_order' => 1],
            ['type' => 'exit_type', 'name' => 'Mengundurkan Diri', 'value' => 'Mengundurkan Diri', 'sort_order' => 2],
            ['type' => 'exit_type', 'name' => 'Meninggal Dunia', 'value' => 'Meninggal Dunia', 'sort_order' => 3],
            ['type' => 'exit_type', 'name' => 'Pensiun', 'value' => 'Pensiun', 'sort_order' => 4],
            
            // Jenis Pertemuan (Meeting Types)
            ['type' => 'meeting_type', 'name' => 'Pertemuan Offline', 'value' => 'Pertemuan Offline', 'sort_order' => 1],
            ['type' => 'meeting_type', 'name' => 'Pertemuan Online', 'value' => 'Pertemuan Online', 'sort_order' => 2],
            
            // Keterangan Lokasi (Location Types)
            ['type' => 'location_type', 'name' => 'Office', 'value' => 'Office', 'sort_order' => 1],
            ['type' => 'location_type', 'name' => 'Patroli', 'value' => 'Patroli', 'sort_order' => 2],
        ];

        foreach ($data as $item) {
            MasterLookup::firstOrCreate(
                ['type' => $item['type'], 'value' => $item['value']],
                $item
            );
        }
    }
}
