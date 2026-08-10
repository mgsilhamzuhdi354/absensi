<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesAndAttachCompanyId extends Migration
{
    private const PRIMARY_COMPANY_CODE = 'IOS';
    private const PRIMARY_COMPANY_NAME = 'PT Indoocean Crew Service';

    private array $companyTables = [
        'users',
        'shifts',
        'lokasis',
        'jabatans',
        'kategoris',
        'jenis_kinerjas',
        'golongans',
        'status_ptkps',
        'tunjangans',
        'reset_cutis',
        'settings',
        'counters',
        'inventories',
        'inventory_stock_transactions',
        'inventory_bast_documents',
        'inventory_stock_variants',
        'inventory_return_documents',
        'pegawai_keluar_asset_clearances',
        'atks',
        'atk_stock_transactions',
        'atk_stock_variants',
        'stock_alerts',
        'mapping_shifts',
        'lemburs',
        'cutis',
        'sips',
        'dinas_luars',
        'pengajuan_dinas_luars',
        'files',
        'kasbons',
        'payrolls',
        'pajaks',
        'reimbursements',
        'reimbursements_items',
        'kunjungans',
        'laporan_kinerjas',
        'penugasans',
        'penugasan_items',
        'rapats',
        'rapat_pegawais',
        'rapat_notulens',
        'kontraks',
        'pegawai_keluars',
        'patrolis',
        'target_kinerjas',
        'target_kinerja_teams',
        'laporan_kerjas',
        'pengajuan_keuangans',
        'pengajuan_keuangan_items',
        'beritas',
        'auto_shifts',
        'pwa_push_subscriptions',
        'daily_attendance_codes',
        'master_lookups',
    ];

    public function up()
    {
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 20)->unique();
                $table->string('logo')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        $primaryCompanyId = DB::table('companies')->updateOrInsert(
            ['code' => self::PRIMARY_COMPANY_CODE],
            [
                'name' => self::PRIMARY_COMPANY_NAME,
                'email' => 'ios@indooceancrew.co.id',
                'phone' => '+62 822-6012-1933',
                'address' => 'Jakarta, Indonesia',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('companies')->updateOrInsert(
            ['code' => 'CAB2'],
            [
                'name' => 'Perusahaan Cabang 2',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $primaryCompanyId = DB::table('companies')
            ->where('code', self::PRIMARY_COMPANY_CODE)
            ->value('id');

        foreach ($this->companyTables as $table) {
            $this->addCompanyColumn($table, $primaryCompanyId);
        }

        $this->addCounterCompanyUniqueness();
    }

    public function down()
    {
        if (Schema::hasTable('counters')) {
            $this->dropIndexIfExists('counters', 'counters_company_name_unique');
        }

        foreach (array_reverse($this->companyTables) as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('company_id');
                });
            }
        }

        Schema::dropIfExists('companies');
    }

    private function addCompanyColumn(string $table, int $primaryCompanyId): void
    {
        if (!Schema::hasTable($table) || Schema::hasColumn($table, 'company_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->index('company_id');
        });

        DB::table($table)->whereNull('company_id')->update([
            'company_id' => $primaryCompanyId,
        ]);
    }

    private function addCounterCompanyUniqueness(): void
    {
        if (!Schema::hasTable('counters') || !Schema::hasColumn('counters', 'company_id')) {
            return;
        }

        try {
            Schema::table('counters', function (Blueprint $table) {
                $table->unique(['company_id', 'name'], 'counters_company_name_unique');
            });
        } catch (Throwable $e) {
            // Existing installs may already have an equivalent index.
        }
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        try {
            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->dropUnique($indexName);
            });
        } catch (Throwable $e) {
            // Ignore when the index does not exist on a given database engine.
        }
    }
}
