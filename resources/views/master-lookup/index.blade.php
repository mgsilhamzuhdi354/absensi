@extends('templates.dashboard')
@section('isi')
    <div class="row">
        <div class="col-md-12 project-list">
            <div class="card">
                <div class="row">
                    <div class="col-md-6 mt-2 p-0 d-flex">
                        <h4>{{ $title }}</h4>
                    </div>
                    <div class="col-md-6 p-0">
                        <a href="{{ url('/master-data/tambah') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Tambah</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <form action="{{ url('/master-data') }}">
                        <div class="row mb-2">
                            <div class="col-md-3">
                                <label>Filter Modul</label>
                                <select name="type" class="form-control selectpicker" data-live-search="true">
                                    <option value="">-- Semua Modul --</option>
                                    @foreach ($types as $key => $label)
                                        <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Cari</label>
                                <input type="text" placeholder="Search...." class="form-control" value="{{ request('search') }}" name="search">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-info"><i class="fa fa-search"></i> Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table id="mytable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">No.</th>
                                    <th>Modul</th>
                                    <th>Nama</th>
                                    <th>Value</th>
                                    <th style="width: 80px;">Urutan</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($masterLookups as $key => $data)
                                    <tr>
                                        <td>{{ ($masterLookups->currentpage() - 1) * $masterLookups->perpage() + $key + 1 }}.</td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $data->type_label }}</span>
                                        </td>
                                        <td>{{ $data->name }}</td>
                                        <td>{{ $data->value }}</td>
                                        <td class="text-center">{{ $data->sort_order }}</td>
                                        <td>
                                            @if ($data->active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger">Non-Aktif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <ul class="action">
                                                <li class="edit">
                                                    <a href="{{ url('/master-data/edit/'.$data->id) }}"><i class="fa fa-solid fa-edit"></i></a>
                                                </li>
                                                <li class="delete">
                                                    <form action="{{ url('/master-data/delete/'.$data->id) }}" method="post" class="d-inline">
                                                        @method('delete')
                                                        @csrf
                                                        <button class="border-0" style="background-color: transparent;" onClick="return confirm('Yakin ingin menghapus data ini?')"><i class="fa fa-solid fa-trash"></i></button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fa fa-inbox fa-3x mb-3"></i>
                                            <p>Belum ada data master</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        {{ $masterLookups->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Info Cards for each type -->
    <div class="row mt-4">
        @foreach ($types as $key => $label)
            <div class="col-md-4 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">{{ $label }}</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ \App\Models\MasterLookup::where('type', $key)->where('active', 1)->count() }} Aktif
                                </div>
                            </div>
                            <div class="col-auto">
                                <a href="{{ url('/master-data?type=' . $key) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-eye"></i> Lihat
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
