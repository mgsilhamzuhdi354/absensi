<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePegawaiKeluarAssetClearancesTable extends Migration
{
    public function up()
    {
        Schema::create('pegawai_keluar_asset_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_keluar_id')->constrained('pegawai_keluars')->cascadeOnDelete();
            $table->foreignId('inventory_stock_transaction_id');
            $table->foreignId('returned_inventory_stock_transaction_id')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('waived_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('waived_at')->nullable();
            $table->text('waiver_reason')->nullable();
            $table->timestamps();

            $table->unique(['pegawai_keluar_id', 'inventory_stock_transaction_id'], 'exit_asset_clearance_unique');
            $table->index(['pegawai_keluar_id', 'status']);
            $table->foreign('inventory_stock_transaction_id', 'pk_asset_clearance_out_tx_fk')
                ->references('id')
                ->on('inventory_stock_transactions')
                ->cascadeOnDelete();
            $table->foreign('returned_inventory_stock_transaction_id', 'pk_asset_clearance_return_tx_fk')
                ->references('id')
                ->on('inventory_stock_transactions')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pegawai_keluar_asset_clearances');
    }
}
