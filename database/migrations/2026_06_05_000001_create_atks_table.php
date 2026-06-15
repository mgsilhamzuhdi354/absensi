<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAtksTable extends Migration
{
    public function up()
    {
        Schema::create('atks', function (Blueprint $table) {
            $table->id();
            $table->string('kode_atk');
            $table->string('nama_atk');
            $table->string('kategori')->nullable();
            $table->float('stok')->default(0);
            $table->string('satuan');
            $table->string('lokasi')->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('active')->nullable()->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('atks');
    }
}
