<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAtkStockTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('atk_stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atk_id')->constrained('atks')->cascadeOnDelete();
            $table->string('jenis_transaksi', 20);
            $table->decimal('jumlah', 15, 2);
            $table->decimal('stok_sebelum', 15, 2);
            $table->decimal('stok_sesudah', 15, 2);
            $table->date('tanggal_transaksi');
            $table->string('sumber_barang')->nullable();
            $table->string('penerima_barang')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['atk_id', 'jenis_transaksi']);
            $table->index('tanggal_transaksi');
        });
    }

    public function down()
    {
        Schema::dropIfExists('atk_stock_transactions');
    }
}
