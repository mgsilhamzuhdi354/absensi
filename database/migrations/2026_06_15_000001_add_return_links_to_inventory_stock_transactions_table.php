<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReturnLinksToInventoryStockTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('inventory_stock_transactions', function (Blueprint $table) {
            $table->foreignId('return_for_transaction_id')
                ->nullable()
                ->after('inventory_id')
                ->constrained('inventory_stock_transactions')
                ->nullOnDelete();
            $table->foreignId('pegawai_keluar_id')
                ->nullable()
                ->after('return_for_transaction_id')
                ->constrained('pegawai_keluars')
                ->nullOnDelete();

            $table->index(['return_for_transaction_id', 'pegawai_keluar_id'], 'inventory_stock_return_exit_index');
        });
    }

    public function down()
    {
        Schema::table('inventory_stock_transactions', function (Blueprint $table) {
            $table->dropIndex('inventory_stock_return_exit_index');
            $table->dropForeign(['return_for_transaction_id']);
            $table->dropForeign(['pegawai_keluar_id']);
            $table->dropColumn([
                'return_for_transaction_id',
                'pegawai_keluar_id',
            ]);
        });
    }
}
