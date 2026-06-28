<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmployeeQrFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_qr_token')->nullable()->unique()->after('employee_id');
            $table->text('employee_qr_profile_value')->nullable()->after('employee_qr_token');
            $table->string('employee_qr_profile_image')->nullable()->after('employee_qr_profile_value');
            $table->text('employee_qr_vcard_value')->nullable()->after('employee_qr_profile_image');
            $table->string('employee_qr_vcard_image')->nullable()->after('employee_qr_vcard_value');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['employee_qr_token']);
            $table->dropColumn([
                'employee_qr_token',
                'employee_qr_profile_value',
                'employee_qr_profile_image',
                'employee_qr_vcard_value',
                'employee_qr_vcard_image',
            ]);
        });
    }
}
