<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CompanyContext
{
    public const SESSION_KEY = 'active_company_id';
    private const ALL_COMPANIES = 'all';
    private const DEFAULT_CODE = 'IOS';

    public function currentCompanyId(): ?int
    {
        if (!$this->companiesReady()) {
            return null;
        }

        $user = Auth::user();
        if ($user && $this->canChooseCompany($user)) {
            $selected = session(self::SESSION_KEY);

            if ($selected === self::ALL_COMPANIES) {
                return null;
            }

            if ($selected && Company::whereKey($selected)->active()->exists()) {
                return (int) $selected;
            }
        }

        if (!$user) {
            $selected = session(self::SESSION_KEY);
            if ($selected && Company::whereKey($selected)->active()->exists()) {
                return (int) $selected;
            }
        }

        if ($user && $user->company_id) {
            return (int) $user->company_id;
        }

        return $this->defaultCompanyId();
    }

    public function currentCompany(): ?Company
    {
        $companyId = $this->currentCompanyId();

        return $companyId ? Company::find($companyId) : null;
    }

    public function defaultCompanyId(): ?int
    {
        if (!$this->companiesReady()) {
            return null;
        }

        return Company::where('code', self::DEFAULT_CODE)->value('id')
            ?: Company::orderBy('id')->value('id');
    }

    public function activeCompanies()
    {
        if (!$this->companiesReady()) {
            return collect();
        }

        return Company::active()->orderBy('name')->get();
    }

    public function isAllCompanies(): bool
    {
        $user = Auth::user();

        return $user
            && $this->canChooseCompany($user)
            && session(self::SESSION_KEY) === self::ALL_COMPANIES;
    }

    public function setActiveCompany($companyId): void
    {
        $user = Auth::user();
        if (!$user || !$this->canChooseCompany($user)) {
            session()->forget(self::SESSION_KEY);
            return;
        }

        if ($companyId === self::ALL_COMPANIES) {
            session([self::SESSION_KEY => self::ALL_COMPANIES]);
            return;
        }

        if (Company::whereKey($companyId)->active()->exists()) {
            session([self::SESSION_KEY => (int) $companyId]);
        }
    }

    public function canChooseCompany($user): bool
    {
        return (string) ($user->is_admin ?? '') === 'admin'
            || (method_exists($user, 'hasRole') && $user->hasRole('admin'));
    }

    private function companiesReady(): bool
    {
        return Schema::hasTable('companies');
    }
}
