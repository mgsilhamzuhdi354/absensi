<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKeteranganToLaporanKinerjasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('laporan_kinerjas', function (Blueprint $table) {
            $table->text('keterangan')->nullable()->after('penilaian_berjalan');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('laporan_kinerjas', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
}
