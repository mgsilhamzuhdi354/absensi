<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBpjsPercentageToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('bpjs_jht_karyawan_persen', 5, 2)->default(2.00)->after('theme_mode');     // 2% karyawan
            $table->decimal('bpjs_jkk_persen', 5, 2)->default(0.24)->after('bpjs_jht_karyawan_persen');      // 0.24% perusahaan
            $table->decimal('bpjs_jkm_persen', 5, 2)->default(0.30)->after('bpjs_jkk_persen');               // 0.30% perusahaan
            $table->decimal('bpjs_jht_perusahaan_persen', 5, 2)->default(3.70)->after('bpjs_jkm_persen');    // 3.7% perusahaan
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'bpjs_jht_karyawan_persen',
                'bpjs_jkk_persen',
                'bpjs_jkm_persen',
                'bpjs_jht_perusahaan_persen'
            ]);
        });
    }
}
