<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPurchaseProofToInventoriesTable extends Migration
{
    public function up()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->string('bukti_pembelian')->nullable()->after('foto_barang');
            $table->string('bukti_pembelian_nama_asli')->nullable()->after('bukti_pembelian');
        });
    }

    public function down()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn([
                'bukti_pembelian',
                'bukti_pembelian_nama_asli',
            ]);
        });
    }
}
