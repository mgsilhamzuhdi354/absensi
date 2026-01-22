<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttendanceSecurityToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            // Attendance Security Mode: 'location', 'ip', 'qr', 'location_qr'
            $table->string('attendance_mode')->default('location')->after('bpjs_jht_perusahaan_persen');
            
            // IP Restriction Settings
            $table->boolean('enable_ip_restriction')->default(false)->after('attendance_mode');
            $table->text('allowed_ip_addresses')->nullable()->after('enable_ip_restriction'); // JSON array of IPs
            $table->string('ip_restriction_message')->default('Anda harus terhubung ke WiFi Kantor untuk absensi')->after('allowed_ip_addresses');
            
            // Daily QR Code Settings
            $table->boolean('enable_daily_qr')->default(false)->after('ip_restriction_message');
            $table->enum('qr_rotation', ['daily', 'hourly'])->default('daily')->after('enable_daily_qr');
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
                'attendance_mode',
                'enable_ip_restriction',
                'allowed_ip_addresses',
                'ip_restriction_message',
                'enable_daily_qr',
                'qr_rotation'
            ]);
        });
    }
}
