<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NormalizeNumericInventoryUom extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('inventories') || !Schema::hasColumn('inventories', 'uom')) {
            return;
        }

        DB::table('inventories')
            ->select('id', 'uom')
            ->orderBy('id')
            ->chunkById(100, function ($inventories) {
                foreach ($inventories as $inventory) {
                    $uom = trim((string) $inventory->uom);

                    if ($uom !== '' && preg_match('/^\d+(?:[,.]\d+)?$/', $uom)) {
                        DB::table('inventories')
                            ->where('id', $inventory->id)
                            ->update(['uom' => 'Unit']);
                    }
                }
            });
    }

    public function down()
    {
        // Data normalization is intentionally not reversed.
    }
}
