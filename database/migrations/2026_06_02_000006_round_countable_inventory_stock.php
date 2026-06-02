<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoundCountableInventoryStock extends Migration
{
    private array $wholeStockUoms = [
        'unit',
        'pcs',
        'pc',
        'piece',
        'pieces',
        'set',
        'box',
        'pack',
        'buah',
    ];

    public function up()
    {
        if (!Schema::hasTable('inventories') || !Schema::hasColumn('inventories', 'stok') || !Schema::hasColumn('inventories', 'uom')) {
            return;
        }

        DB::table('inventories')
            ->select('id', 'stok', 'uom')
            ->orderBy('id')
            ->chunkById(100, function ($inventories) {
                foreach ($inventories as $inventory) {
                    $uom = $this->normalizeUom($inventory->uom);
                    $stock = (float) ($inventory->stok ?? 0);
                    $roundedStock = round($stock);

                    if (!in_array($uom, $this->wholeStockUoms, true) || abs($stock - $roundedStock) < 0.000001) {
                        continue;
                    }

                    DB::table('inventories')
                        ->where('id', $inventory->id)
                        ->update(['stok' => max(0, $roundedStock)]);
                }
            });
    }

    public function down()
    {
        // Data cleanup is intentionally not reversed.
    }

    private function normalizeUom($uom): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower(trim((string) $uom))) ?? '';
    }
}
