<?php

if (!function_exists('settings')) {
    /**
     * Get application settings singleton
     */
    function settings()
    {
        return \App\Models\settings::first()
            ?: \App\Models\settings::withoutGlobalScope('company')->first();
    }
}

if (!function_exists('company_context')) {
    function company_context()
    {
        return app(\App\Services\CompanyContext::class);
    }
}

if (!function_exists('current_company_id')) {
    function current_company_id()
    {
        return company_context()->currentCompanyId();
    }
}

if (!function_exists('current_company')) {
    function current_company()
    {
        return company_context()->currentCompany();
    }
}
