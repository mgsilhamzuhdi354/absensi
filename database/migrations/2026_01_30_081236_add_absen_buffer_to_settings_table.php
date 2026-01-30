<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAbsenBufferToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->integer('absen_masuk_buffer_menit')->default(30)->after('qr_rotation');
            $table->integer('absen_pulang_buffer_menit')->default(30)->after('absen_masuk_buffer_menit');
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
            $table->dropColumn(['absen_masuk_buffer_menit', 'absen_pulang_buffer_menit']);
        });
    }
}
