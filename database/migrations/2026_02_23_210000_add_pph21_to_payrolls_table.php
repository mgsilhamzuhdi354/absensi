<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('pph21_persen', 5, 2)->default(0)->after('loss');
            $table->bigInteger('pph21_amount')->default(0)->after('pph21_persen');
        });
    }

    public function down()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['pph21_persen', 'pph21_amount']);
        });
    }
};
