<?php

namespace App\Http\Controllers;

use App\Models\MasterLookup;
use Illuminate\Http\Request;

class MasterLookupController extends Controller
{
    public function index()
    {
        $title = 'Master Data';
        $type = request()->input('type');
        $search = request()->input('search');
        
        $masterLookups = MasterLookup::when($type, function ($query) use ($type) {
                            $query->where('type', $type);
                        })
                        ->when($search, function ($query) use ($search) {
                            $query->where('name', 'LIKE', '%'.$search.'%');
                        })
                        ->orderBy('type', 'ASC')
                        ->orderBy('sort_order', 'ASC')
                        ->orderBy('name', 'ASC')
                        ->paginate(15)
                        ->withQueryString();

        $types = MasterLookup::getTypes();

        return view('master-lookup.index', compact(
            'title',
            'masterLookups',
            'types',
            'type'
        ));
    }

    public function tambah()
    {
        $title = 'Tambah Master Data';
        $types = MasterLookup::getTypes();
        
        return view('master-lookup.tambah', compact(
            'title',
            'types'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required',
            'name' => 'required',
            'value' => 'nullable',
            'active' => 'nullable',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['active'] = $request->has('active') ? 1 : 0;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['value'] = $validated['value'] ?? $validated['name'];

        MasterLookup::create($validated);
        
        return redirect('/master-data')->with('success', 'Data Berhasil Disimpan');
    }

    public function edit($id)
    {
        $masterLookup = MasterLookup::find($id);
        $title = 'Edit Master Data';
        $types = MasterLookup::getTypes();
        
        return view('master-lookup.edit', compact(
            'title',
            'masterLookup',
            'types'
        ));
    }

    public function update(Request $request, $id)
    {
        $masterLookup = MasterLookup::find($id);
        
        $validated = $request->validate([
            'type' => 'required',
            'name' => 'required',
            'value' => 'nullable',
            'active' => 'nullable',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['active'] = $request->has('active') ? 1 : 0;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['value'] = $validated['value'] ?? $validated['name'];

        $masterLookup->update($validated);
        
        return redirect('/master-data')->with('success', 'Data Berhasil Diupdate');
    }

    public function delete($id)
    {
        $masterLookup = MasterLookup::find($id);
        $masterLookup->delete();
        
        return redirect('/master-data')->with('success', 'Data Berhasil Dihapus');
    }
}
