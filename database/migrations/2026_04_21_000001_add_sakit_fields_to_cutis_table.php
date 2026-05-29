<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSakitFieldsToCutisTable extends Migration
{
    public function up()
    {
        Schema::table('cutis', function (Blueprint $table) {
            // tipe_sakit: 'surat_dokter', 'tanpa_surat_dokter', 'keluarga_meninggal'
            $table->string('tipe_sakit')->nullable()->after('foto_cuti');
            // potongan_gaji: nominal potongan yang diisi admin (hanya untuk tanpa surat dokter)
            $table->decimal('potongan_gaji', 15, 2)->nullable()->default(0)->after('tipe_sakit');
        });
    }

    public function down()
    {
        Schema::table('cutis', function (Blueprint $table) {
            $table->dropColumn(['tipe_sakit', 'potongan_gaji']);
        });
    }
}
