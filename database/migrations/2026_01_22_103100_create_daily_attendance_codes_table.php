<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyAttendanceCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_attendance_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique(); // Unique hash code
            $table->date('date'); // Valid date
            $table->text('qr_image')->nullable(); // Base64 QR image
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index('date');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('daily_attendance_codes');
    }
}
