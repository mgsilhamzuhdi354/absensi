<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStockAlertEnabledToAtksAndInventoriesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('atks') && !Schema::hasColumn('atks', 'stock_alert_enabled')) {
            Schema::table('atks', function (Blueprint $table) {
                $table->boolean('stock_alert_enabled')->default(true)->after('active');
            });
        }

        if (Schema::hasTable('inventories') && !Schema::hasColumn('inventories', 'stock_alert_enabled')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->boolean('stock_alert_enabled')->default(true)->after('stok');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('atks') && Schema::hasColumn('atks', 'stock_alert_enabled')) {
            Schema::table('atks', function (Blueprint $table) {
                $table->dropColumn('stock_alert_enabled');
            });
        }

        if (Schema::hasTable('inventories') && Schema::hasColumn('inventories', 'stock_alert_enabled')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->dropColumn('stock_alert_enabled');
            });
        }
    }
}
