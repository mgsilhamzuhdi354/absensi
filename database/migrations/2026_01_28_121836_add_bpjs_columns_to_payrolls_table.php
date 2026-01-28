<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBpjsColumnsToPayrollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('bpjs_tk_karyawan', 15, 2)->default(0)->after('grand_total');
            $table->decimal('bpjs_jkk', 15, 2)->default(0)->after('bpjs_tk_karyawan');
            $table->decimal('bpjs_jkm', 15, 2)->default(0)->after('bpjs_jkk');
            $table->decimal('bpjs_tk_perusahaan', 15, 2)->default(0)->after('bpjs_jkm');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['bpjs_tk_karyawan', 'bpjs_jkk', 'bpjs_jkm', 'bpjs_tk_perusahaan']);
        });
    }
}
