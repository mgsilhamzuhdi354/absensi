<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQrAndAssetFieldsToInventoriesTable extends Migration
{
    public function up()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->string('jenis_barang')->nullable()->after('nama_barang');
            $table->string('merk_tipe')->nullable()->after('jenis_barang');
            $table->string('serial_number')->nullable()->after('merk_tipe');
            $table->text('spesifikasi')->nullable()->after('serial_number');
            $table->string('kondisi')->nullable()->after('spesifikasi');
            $table->string('status_barang')->nullable()->after('kondisi');
            $table->date('tanggal_masuk')->nullable()->after('status_barang');
            $table->string('foto_barang')->nullable()->after('tanggal_masuk');
            $table->text('qr_code_value')->nullable()->after('foto_barang');
            $table->string('qr_code_image')->nullable()->after('qr_code_value');
            $table->string('qr_token')->nullable()->after('qr_code_image');

            $table->unique('qr_token');
        });
    }

    public function down()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn([
                'jenis_barang',
                'merk_tipe',
                'serial_number',
                'spesifikasi',
                'kondisi',
                'status_barang',
                'tanggal_masuk',
                'foto_barang',
                'qr_code_value',
                'qr_code_image',
                'qr_token',
            ]);
        });
    }
}
