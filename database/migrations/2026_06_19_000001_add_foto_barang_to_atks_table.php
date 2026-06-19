<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFotoBarangToAtksTable extends Migration
{
    public function up()
    {
        Schema::table('atks', function (Blueprint $table) {
            $table->string('foto_barang')->nullable()->after('keterangan');
        });
    }

    public function down()
    {
        Schema::table('atks', function (Blueprint $table) {
            $table->dropColumn('foto_barang');
        });
    }
}
