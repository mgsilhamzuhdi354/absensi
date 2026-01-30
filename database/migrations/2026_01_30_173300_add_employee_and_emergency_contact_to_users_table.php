<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Menambahkan field Employee ID dan Kontak Darurat ke tabel users
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Employee ID
            $table->string('employee_id')->nullable()->after('jabatan_id');

            // Kontak Darurat
            $table->string('nama_kontak_darurat')->nullable()->after('masa_berlaku');
            $table->string('telepon_kontak_darurat')->nullable()->after('nama_kontak_darurat');
            $table->string('hubungan_kontak_darurat')->nullable()->after('telepon_kontak_darurat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'employee_id',
                'nama_kontak_darurat',
                'telepon_kontak_darurat',
                'hubungan_kontak_darurat'
            ]);
        });
    }
};
