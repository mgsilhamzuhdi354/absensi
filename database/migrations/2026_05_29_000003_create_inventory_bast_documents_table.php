<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryBastDocumentsTable extends Migration
{
    public function up()
    {
        Schema::create('inventory_bast_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_stock_transaction_id')
                ->unique()
                ->constrained('inventory_stock_transactions')
                ->cascadeOnDelete();
            $table->string('nomor_surat')->unique();
            $table->date('tanggal_surat');
            $table->string('nama_penerima')->nullable();
            $table->string('jabatan_penerima')->nullable();
            $table->string('nama_penyerah')->nullable();
            $table->string('jabatan_penyerah')->nullable();
            $table->string('nama_mengetahui')->nullable();
            $table->string('file_pdf')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_bast_documents');
    }
}
