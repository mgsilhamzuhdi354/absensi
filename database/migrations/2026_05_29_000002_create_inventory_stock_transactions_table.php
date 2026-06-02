<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryStockTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('inventory_stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->string('jenis_transaksi', 20);
            $table->decimal('jumlah', 15, 2);
            $table->decimal('stok_sebelum', 15, 2);
            $table->decimal('stok_sesudah', 15, 2);
            $table->date('tanggal_transaksi');
            $table->string('sumber_barang')->nullable();
            $table->foreignId('penerima_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('penerima_barang')->nullable();
            $table->string('jabatan_penerima')->nullable();
            $table->string('departemen_penerima')->nullable();
            $table->string('keperluan')->nullable();
            $table->string('kondisi_barang')->nullable();
            $table->foreignId('lokasi_id')->nullable()->constrained('lokasis')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['inventory_id', 'jenis_transaksi']);
            $table->index('tanggal_transaksi');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_stock_transactions');
    }
}
