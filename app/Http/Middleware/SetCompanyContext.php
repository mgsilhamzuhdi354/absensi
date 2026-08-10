<?php

namespace App\Http\Middleware;

use App\Services\CompanyContext;
use Closure;
use Illuminate\Support\Facades\View;

class SetCompanyContext
{
    public function handle($request, Closure $next)
    {
        $context = app(CompanyContext::class);

        View::share('currentCompany', $context->currentCompany());
        View::share('companyOptions', $context->activeCompanies());
        View::share('isAllCompanies', $context->isAllCompanies());

        return $next($request);
    }
}
