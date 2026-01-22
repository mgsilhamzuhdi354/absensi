@extends('templates.dashboard')
@section('isi')
    <div class="row">
        <div class="col-md-12 m project-list">
            <div class="card">
                <div class="row">
                    <div class="col-md-6 p-0 d-flex mt-2">
                        <h4>{{ $title }}</h4>
                    </div>
                    <div class="col-md-6 p-0">
                        <a href="{{ url('/pegawai/shift/'.$user_id) }}" class="btn btn-danger btn-sm ms-2">Back</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Edit {{ count($selected_shifts) }} Shift Sekaligus</h5>
                </div>
                <form method="post" action="{{ url('/pegawai/proses-bulk-edit-shift') }}" class="p-4">
                    @method('put')
                    @csrf
                    
                    <!-- Hidden inputs for selected shift IDs -->
                    @foreach($selected_shifts as $sk)
                        <input type="hidden" name="shift_ids[]" value="{{ $sk->id }}">
                    @endforeach
                    <input type="hidden" name="user_id" value="{{ $user_id }}">
                    
                    <!-- Preview selected shifts -->
                    <div class="mb-4">
                        <label class="form-label">Shift yang akan diubah:</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Shift Lama</th>
                                        <th>Jam Lama</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($selected_shifts as $sk)
                                        <tr>
                                            <td>{{ $sk->tanggal }}</td>
                                            <td>{{ $sk->Shift->nama_shift ?? '-' }}</td>
                                            <td>{{ $sk->Shift->jam_masuk ?? '-' }} - {{ $sk->Shift->jam_keluar ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- New shift selection -->
                    <div class="form-group mb-3">
                        <label for="shift_id" class="form-label">Shift Baru (untuk semua)</label>
                        <select class="form-control selectpicker @error('shift_id') is-invalid @enderror" id="shift_id" name="shift_id" data-live-search="true" required>
                            <option value="">Pilih Shift Baru</option>
                            @foreach ($shift as $s)
                                <option value="{{ $s->id }}">{{ $s->nama_shift . " (" . $s->jam_masuk . " - " . $s->jam_keluar . ") " }}</option>
                            @endforeach
                        </select>
                        @error('shift_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    
                    <div class="form-check mb-4">
                        <input name="lock_location" class="form-check-input" type="checkbox" value="1" id="lock_location">
                        <label class="form-check-label" for="lock_location">
                            Lock Location (untuk semua)
                        </label>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ url('/pegawai/shift/'.$user_id) }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-1"></i> Update {{ count($selected_shifts) }} Shift
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
