<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPartyDepartmentAndManualLockToInventoryBastDocumentsTable extends Migration
{
    public function up()
    {
        Schema::table('inventory_bast_documents', function (Blueprint $table) {
            $table->string('departemen_penerima')->nullable()->after('jabatan_penerima');
            $table->string('departemen_penyerah')->nullable()->after('jabatan_penyerah');
            $table->boolean('party_details_locked')->default(false)->after('nama_mengetahui');
        });
    }

    public function down()
    {
        Schema::table('inventory_bast_documents', function (Blueprint $table) {
            $table->dropColumn([
                'departemen_penerima',
                'departemen_penyerah',
                'party_details_locked',
            ]);
        });
    }
}
