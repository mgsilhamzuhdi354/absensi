@extends('templates.dashboard')

@section('isi')
    <div class="row">
        <div class="col-md-12 project-list">
            <div class="card">
                <div class="row align-items-center">
                    <div class="col-md-6 mt-2 p-0 d-flex">
                        <h4>{{ $title }}</h4>
                    </div>
                    <div class="col-md-6 p-0 text-end">
                        <a href="{{ url('/perusahaan/tambah') }}" class="btn btn-primary">+ Tambah</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            @if (!empty($migrationWarning))
                <div class="alert alert-warning">
                    {{ $migrationWarning }}
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th class="text-center">No.</th>
                                    <th>Nama</th>
                                    <th>Kode</th>
                                    <th>Email</th>
                                    <th>Telepon</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($companies as $key => $company)
                                    <tr>
                                        <td class="text-center">{{ ($companies->currentPage() - 1) * $companies->perPage() + $key + 1 }}.</td>
                                        <td>{{ $company->name }}</td>
                                        <td>{{ $company->code }}</td>
                                        <td>{{ $company->email ?? '-' }}</td>
                                        <td>{{ $company->phone ?? '-' }}</td>
                                        <td>
                                            <span class="badge {{ $company->active ? 'badge-success' : 'badge-danger' }}">
                                                {{ $company->active ? 'Aktif' : 'Non-Aktif' }}
                                            </span>
                                        </td>
                                        <td>
                                            <ul class="action">
                                                <li class="edit">
                                                    <a href="{{ url('/perusahaan/edit/'.$company->id) }}"><i class="fa fa-solid fa-edit"></i></a>
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak Ada Data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        {{ $companies->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
