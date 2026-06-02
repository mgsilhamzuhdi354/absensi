<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeleteTrackingToInventoryStockTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('inventory_stock_transactions', function (Blueprint $table) {
            $table->foreignId('deleted_by')->nullable()->after('diproses_oleh')->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('inventory_stock_transactions', function (Blueprint $table) {
            $table->dropForeign(['deleted_by']);
            $table->dropColumn('deleted_by');
            $table->dropSoftDeletes();
        });
    }
}
