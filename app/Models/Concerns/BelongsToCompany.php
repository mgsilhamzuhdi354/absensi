<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany()
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (!static::companyColumnReady()) {
                return;
            }

            $companyId = app(CompanyContext::class)->currentCompanyId();
            if ($companyId) {
                $table = $builder->getModel()->getTable();

                $builder->where(function (Builder $query) use ($table, $companyId) {
                    $query->where($table . '.company_id', $companyId)
                        ->orWhereNull($table . '.company_id');
                });
            }
        });

        static::creating(function ($model) {
            if (!static::companyColumnReady() || $model->company_id) {
                return;
            }

            $model->company_id = app(CompanyContext::class)->currentCompanyId()
                ?: app(CompanyContext::class)->defaultCompanyId();
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany(Builder $query, $companyId)
    {
        return $companyId ? $query->where($query->getModel()->getTable() . '.company_id', $companyId) : $query;
    }

    protected static function companyColumnReady(): bool
    {
        $model = new static;

        return Schema::hasTable($model->getTable())
            && Schema::hasColumn($model->getTable(), 'company_id');
    }
}
