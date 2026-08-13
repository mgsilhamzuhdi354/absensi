<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('companies')) {
            return view('companies.index', [
                'title' => 'Perusahaan',
                'companies' => new LengthAwarePaginator([], 0, 10),
                'migrationWarning' => 'Data perusahaan belum siap. Jalankan php artisan migrate --force di server agar menu ini bisa digunakan.',
            ]);
        }

        return view('companies.index', [
            'title' => 'Perusahaan',
            'companies' => Company::orderBy('name')->paginate(10)->withQueryString(),
        ]);
    }

    public function tambah()
    {
        return view('companies.form', [
            'title' => 'Tambah Perusahaan',
            'company' => null,
        ]);
    }

    public function store(Request $request)
    {
        Company::create($this->validatedCompany($request));

        return redirect('/perusahaan')->with('success', 'Perusahaan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        return view('companies.form', [
            'title' => 'Edit Perusahaan',
            'company' => Company::findOrFail($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $company->update($this->validatedCompany($request, $company));

        return redirect('/perusahaan')->with('success', 'Perusahaan berhasil diupdate.');
    }

    public function switch(Request $request)
    {
        $validated = $request->validate([
            'company_id' => ['required'],
        ]);

        app(CompanyContext::class)->setActiveCompany($validated['company_id']);

        return back()->with('success', 'Pilihan perusahaan aktif berhasil diganti.');
    }

    private function validatedCompany(Request $request, Company $company = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('companies', 'code')->ignore($company),
            ],
            'logo' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['active'] = $request->boolean('active');

        return $validated;
    }
}
