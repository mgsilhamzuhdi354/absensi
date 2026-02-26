<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('bpjs_jht_persen', 5, 2)->default(2)->after('pph21_amount');
            $table->decimal('bpjs_jht_amount', 15, 2)->default(0)->after('bpjs_jht_persen');
            $table->decimal('bpjs_kes_persen', 5, 2)->default(1)->after('bpjs_jht_amount');
            $table->decimal('bpjs_kes_amount', 15, 2)->default(0)->after('bpjs_kes_persen');
        });
    }

    public function down()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['bpjs_jht_persen', 'bpjs_jht_amount', 'bpjs_kes_persen', 'bpjs_kes_amount']);
        });
    }
};
