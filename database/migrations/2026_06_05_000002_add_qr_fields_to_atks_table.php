<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQrFieldsToAtksTable extends Migration
{
    public function up()
    {
        Schema::table('atks', function (Blueprint $table) {
            $table->text('qr_code_value')->nullable()->after('active');
            $table->string('qr_code_image')->nullable()->after('qr_code_value');
            $table->string('qr_token')->nullable()->after('qr_code_image');

            $table->unique('qr_token');
        });
    }

    public function down()
    {
        Schema::table('atks', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn([
                'qr_code_value',
                'qr_code_image',
                'qr_token',
            ]);
        });
    }
}
