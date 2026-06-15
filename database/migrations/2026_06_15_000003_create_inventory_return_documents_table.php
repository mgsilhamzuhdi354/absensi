<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryReturnDocumentsTable extends Migration
{
    public function up()
    {
        Schema::create('inventory_return_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_keluar_asset_clearance_id');
            $table->foreignId('return_inventory_stock_transaction_id')->nullable();
            $table->foreignId('original_inventory_stock_transaction_id');
            $table->foreignId('pegawai_keluar_id')->constrained('pegawai_keluars')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->string('nomor_surat')->unique();
            $table->date('tanggal_surat');
            $table->foreignId('employee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('it_receiver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('known_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama_pengembali')->nullable();
            $table->string('jabatan_pengembali')->nullable();
            $table->string('departemen_pengembali')->nullable();
            $table->string('nama_penerima')->nullable();
            $table->string('jabatan_penerima')->nullable();
            $table->string('departemen_penerima')->nullable();
            $table->string('nama_mengetahui')->nullable();
            $table->string('kondisi_kembali')->nullable();
            $table->string('kelengkapan')->nullable();
            $table->text('catatan')->nullable();
            $table->string('file_pdf')->nullable();

            $table->string('employee_signature_name')->nullable();
            $table->string('employee_signature_image')->nullable();
            $table->timestamp('employee_signed_at')->nullable();
            $table->string('employee_signature_ip', 45)->nullable();
            $table->text('employee_signature_user_agent')->nullable();

            $table->string('it_receiver_signature_name')->nullable();
            $table->string('it_receiver_signature_image')->nullable();
            $table->timestamp('it_receiver_signed_at')->nullable();
            $table->string('it_receiver_signature_ip', 45)->nullable();
            $table->text('it_receiver_signature_user_agent')->nullable();

            $table->string('known_signature_name')->nullable();
            $table->string('known_signature_image')->nullable();
            $table->timestamp('known_signed_at')->nullable();
            $table->string('known_signature_ip', 45)->nullable();
            $table->text('known_signature_user_agent')->nullable();

            $table->timestamps();

            $table->unique('pegawai_keluar_asset_clearance_id', 'inv_return_clearance_unique');
            $table->unique('return_inventory_stock_transaction_id', 'inv_return_return_tx_unique');
            $table->index(['employee_user_id', 'it_receiver_user_id', 'known_by_user_id'], 'inventory_return_document_signers_index');
            $table->foreign('pegawai_keluar_asset_clearance_id', 'inv_return_clearance_fk')
                ->references('id')
                ->on('pegawai_keluar_asset_clearances')
                ->cascadeOnDelete();
            $table->foreign('return_inventory_stock_transaction_id', 'inv_return_return_tx_fk')
                ->references('id')
                ->on('inventory_stock_transactions')
                ->nullOnDelete();
            $table->foreign('original_inventory_stock_transaction_id', 'inv_return_original_tx_fk')
                ->references('id')
                ->on('inventory_stock_transactions')
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_return_documents');
    }
}
