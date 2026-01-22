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
                        <a href="{{ url('/berita/tambah') }}" class="btn btn-primary">+ Tambah</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <form action="{{ url('/berita') }}">
                        <div class="row mb-2">
                            <div class="col-2">
                                <input type="text" placeholder="Search...." class="form-control" value="{{ request('search') }}" name="search">
                            </div>
                            <div class="col">
                                <button type="submit" id="search"class="border-0 mt-3" style="background-color: transparent;"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="mytable" class="table table-striped" style="vertical-align: middle">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Tipe</th>
                                    <th>Judul</th>
                                    <th>Isi</th>
                                    <th>Gambar</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($berita as $key => $ber)
                                    <tr>
                                        <td>{{ ($berita->currentpage() - 1) * $berita->perpage() + $key + 1 }}.</td>
                                        <td>{{ $ber->tipe ?? '-' }}</td>
                                        <td>{{ $ber->judul ?? '-' }}</td>
                                        <td>{!! $ber->isi !!}</td>
                                        <td>
                                            @if ($ber->berita_file_path)
                                                <a href="{{ url('/storage/'.$ber->berita_file_path) }}" target="_blank"><img style="width: 200px" src="{{ asset('/storage/'.$ber->berita_file_path) }}" alt="{{ $ber->berita_file_name }}"></a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <ul class="action" style="list-style: none; padding: 0; margin: 0; display: flex; gap: 10px;">
                                                <li class="edit">
                                                    <a href="{{ url('/berita/edit/'.$ber->id) }}" style="color: #61ae41;"><i class="fa fa-solid fa-edit" style="font-size: 18px;"></i></a>
                                                </li>
                                                <li class="delete">
                                                    <form action="{{ url('/berita/delete/'.$ber->id) }}" method="post" style="display: inline;">
                                                        @method('delete')
                                                        @csrf
                                                        <button type="submit" style="background: none; border: none; cursor: pointer; color: #f81f58; padding: 0;" onClick="return confirm('Apakah Anda yakin ingin menghapus?')">
                                                            <i class="fa fa-solid fa-trash" style="font-size: 18px;"></i>
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        {{ $berita->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
@endsection
