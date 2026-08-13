<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeCompanyTransfersTable extends Migration
{
    public function up()
    {
        Schema::create('employee_company_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('source_company_id')->nullable()->index();
            $table->unsignedBigInteger('destination_company_id')->index();
            $table->unsignedBigInteger('source_jabatan_id')->nullable();
            $table->unsignedBigInteger('destination_jabatan_id')->nullable();
            $table->unsignedBigInteger('source_lokasi_id')->nullable();
            $table->unsignedBigInteger('destination_lokasi_id')->nullable();
            $table->unsignedBigInteger('transferred_by')->nullable()->index();
            $table->timestamp('transferred_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_company_transfers');
    }
}
