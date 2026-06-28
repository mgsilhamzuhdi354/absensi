<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStockColorVariantsForAtkAndInventory extends Migration
{
    private const DEFAULT_COLOR = 'Umum';

    public function up()
    {
        if (!Schema::hasTable('atk_stock_variants')) {
            Schema::create('atk_stock_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('atk_id')->constrained('atks')->cascadeOnDelete();
                $table->string('warna_barang', 80);
                $table->decimal('stok', 15, 2)->default(0);
                $table->timestamps();

                $table->unique(['atk_id', 'warna_barang'], 'atk_stock_variant_unique_color');
                $table->index(['atk_id', 'stok'], 'atk_stock_variant_stock_index');
            });
        }

        if (!Schema::hasColumn('atk_stock_transactions', 'warna_barang')) {
            Schema::table('atk_stock_transactions', function (Blueprint $table) {
                $table->string('warna_barang', 80)->nullable()->after('jumlah');
            });
        }

        if (!Schema::hasTable('inventory_stock_variants')) {
            Schema::create('inventory_stock_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
                $table->string('warna_barang', 80);
                $table->decimal('stok', 15, 2)->default(0);
                $table->timestamps();

                $table->unique(['inventory_id', 'warna_barang'], 'inventory_stock_variant_unique_color');
                $table->index(['inventory_id', 'stok'], 'inventory_stock_variant_stock_index');
            });
        }

        if (!Schema::hasColumn('inventory_stock_transactions', 'warna_barang')) {
            Schema::table('inventory_stock_transactions', function (Blueprint $table) {
                $table->string('warna_barang', 80)->nullable()->after('jumlah');
            });
        }

        $this->seedExistingAtkStock();
        $this->seedExistingInventoryStock();
    }

    public function down()
    {
        if (Schema::hasColumn('inventory_stock_transactions', 'warna_barang')) {
            Schema::table('inventory_stock_transactions', function (Blueprint $table) {
                $table->dropColumn('warna_barang');
            });
        }

        Schema::dropIfExists('inventory_stock_variants');

        if (Schema::hasColumn('atk_stock_transactions', 'warna_barang')) {
            Schema::table('atk_stock_transactions', function (Blueprint $table) {
                $table->dropColumn('warna_barang');
            });
        }

        Schema::dropIfExists('atk_stock_variants');
    }

    private function seedExistingAtkStock(): void
    {
        if (!Schema::hasTable('atks') || !Schema::hasTable('atk_stock_variants')) {
            return;
        }

        DB::table('atks')
            ->select('id', 'stok', 'created_at', 'updated_at')
            ->where('stok', '>', 0)
            ->orderBy('id')
            ->chunkById(100, function ($items) {
                foreach ($items as $item) {
                    DB::table('atk_stock_variants')->insertOrIgnore([
                        'atk_id' => $item->id,
                        'warna_barang' => self::DEFAULT_COLOR,
                        'stok' => round((float) $item->stok, 2),
                        'created_at' => $item->created_at ?: now(),
                        'updated_at' => $item->updated_at ?: now(),
                    ]);
                }
            });
    }

    private function seedExistingInventoryStock(): void
    {
        if (!Schema::hasTable('inventories') || !Schema::hasTable('inventory_stock_variants')) {
            return;
        }

        DB::table('inventories')
            ->select('id', 'stok', 'created_at', 'updated_at')
            ->where('stok', '>', 0)
            ->orderBy('id')
            ->chunkById(100, function ($items) {
                foreach ($items as $item) {
                    DB::table('inventory_stock_variants')->insertOrIgnore([
                        'inventory_id' => $item->id,
                        'warna_barang' => self::DEFAULT_COLOR,
                        'stok' => round((float) $item->stok, 2),
                        'created_at' => $item->created_at ?: now(),
                        'updated_at' => $item->updated_at ?: now(),
                    ]);
                }
            });
    }
}
