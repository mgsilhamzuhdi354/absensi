<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Jabatan;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RealRashid\SweetAlert\Facades\Alert;

class jabatanController extends Controller
{
    public function index()
    {
        $search = request()->input('search');
        $jabatan = Jabatan::when($search, function ($query) use ($search) {
                        $query->where('nama_jabatan', 'LIKE', '%' . $search . '%');
                    })
                    ->orderBy('nama_jabatan', 'ASC')
                    ->paginate(10)
                    ->withQueryString();

        return view('jabatan.index', [
            'title' => 'Divisi',
            'data_jabatan' => $jabatan
        ]);
    }

    public function create()
    {
        return view('jabatan.create', [
            'title' => 'Tambah Data Divisi',
            'users' => User::select('id', 'name')->forCompany(current_company_id())->orderBy('name')->get(),
            'companies' => Company::active()->orderBy('name')->get(),
        ]);
    }

    public function insert(Request $request)
    {
        $validatedData = $request->validate([
            'nama_jabatan' => 'required|max:255',
            'manager' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('company_id', $this->selectedCompanyId($request))),
            ],
            'company_id' => 'nullable|exists:companies,id',
        ]);
        $validatedData['company_id'] = $this->selectedCompanyId($request);

        Jabatan::create($validatedData);
        return redirect('/jabatan')->with('success', 'Data Berhasil di Tambahkan');
    }

    public function edit($id)
    {
        return view('jabatan.edit', [
            'title' => 'Edit Data Divisi',
            'data_jabatan' => Jabatan::findOrFail($id),
            'users' => User::select('id', 'name')->forCompany(Jabatan::findOrFail($id)->company_id)->orderBy('name')->get(),
            'companies' => Company::active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'nama_jabatan' => 'required|max:255',
            'manager' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('company_id', Jabatan::findOrFail($id)->company_id)),
            ],
            'company_id' => 'nullable|exists:companies,id',
        ]);
        $validatedData['company_id'] = Jabatan::findOrFail($id)->company_id;

        Jabatan::where('id', $id)->update($validatedData);
        return redirect('/jabatan')->with('success', 'Data Berhasil di Update');
    }

    public function delete($id)
    {
        $jabatan = Jabatan::findOrFail($id);
        $user = User::where('jabatan_id', $id)->count();
        if ($user > 0) {
            Alert::error('Failed', 'Masih Ada User Yang Menggunakan Jabatan Ini!');
            return redirect('/jabatan');
        } else {
            $jabatan->delete();
            return redirect('/jabatan')->with('success', 'Data Berhasil di Delete');
        }
    }

    private function selectedCompanyId(Request $request): int
    {
        return (int) ($request->input('company_id') ?: current_company_id() ?: company_context()->defaultCompanyId());
    }
}
