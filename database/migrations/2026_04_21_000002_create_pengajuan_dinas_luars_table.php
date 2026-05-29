<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePengajuanDinasLuarsTable extends Migration
{
    public function up()
    {
        Schema::create('pengajuan_dinas_luars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->string('tanggal_mulai');
            $table->string('tanggal_akhir');
            $table->text('alasan');
            $table->string('lokasi_tujuan')->nullable();
            // Status: Pending, Approved, Ditolak
            $table->string('status')->default('Pending');
            $table->unsignedBigInteger('user_approval')->nullable();
            $table->foreign('user_approval')->references('id')->on('users')->onDelete('set null');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pengajuan_dinas_luars');
    }
}
