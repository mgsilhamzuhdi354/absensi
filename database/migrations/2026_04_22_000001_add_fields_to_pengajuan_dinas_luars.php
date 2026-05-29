<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToPengajuanDinasLuars extends Migration
{
    public function up()
    {
        Schema::table('pengajuan_dinas_luars', function (Blueprint $table) {
            $table->string('foto_bukti')->nullable()->after('lokasi_tujuan');
            $table->string('lat_pengajuan')->nullable()->after('foto_bukti');
            $table->string('long_pengajuan')->nullable()->after('lat_pengajuan');
        });
    }

    public function down()
    {
        Schema::table('pengajuan_dinas_luars', function (Blueprint $table) {
            $table->dropColumn(['foto_bukti', 'lat_pengajuan', 'long_pengajuan']);
        });
    }
}
