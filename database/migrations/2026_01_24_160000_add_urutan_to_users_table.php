<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('urutan')->default(0)->after('id');
        });

        // Set initial order based on name without MySQL-only user variables.
        \DB::table('users')
            ->orderBy('name', 'ASC')
            ->pluck('id')
            ->values()
            ->each(function ($id, $index) {
                \DB::table('users')
                    ->where('id', $id)
                    ->update(['urutan' => $index + 1]);
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('urutan');
        });
    }
};
