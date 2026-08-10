<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetTransfersTable extends Migration
{
    public function up()
    {
        Schema::create('asset_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('source_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('destination_company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('transferable_type');
            $table->unsignedBigInteger('transferable_id');
            $table->unsignedBigInteger('target_transferable_id')->nullable();
            $table->unsignedBigInteger('outgoing_transaction_id')->nullable();
            $table->unsignedBigInteger('incoming_transaction_id')->nullable();
            $table->decimal('jumlah', 15, 2);
            $table->string('warna_barang', 80)->nullable();
            $table->date('tanggal_transfer');
            $table->text('catatan')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['transferable_type', 'transferable_id']);
            $table->index(['transferable_type', 'target_transferable_id']);
            $table->index(['source_company_id', 'destination_company_id']);
            $table->index('tanggal_transfer');
        });
    }

    public function down()
    {
        Schema::dropIfExists('asset_transfers');
    }
}
