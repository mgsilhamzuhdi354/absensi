<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateSecondCompanyToKrb extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        $cab2 = DB::table('companies')->where('code', 'CAB2')->first();
        $krb = DB::table('companies')->where('code', 'KRB')->first();

        if ($cab2 && !$krb) {
            DB::table('companies')
                ->where('id', $cab2->id)
                ->update([
                    'code' => 'KRB',
                    'name' => 'PT KRBPRODUCE',
                    'active' => true,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('companies')->updateOrInsert(
            ['code' => 'KRB'],
            [
                'name' => 'PT KRBPRODUCE',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down()
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        DB::table('companies')
            ->where('code', 'KRB')
            ->update([
                'code' => 'CAB2',
                'name' => 'Perusahaan Cabang 2',
                'updated_at' => now(),
            ]);
    }
}
