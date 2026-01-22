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
                        <a href="{{ url('/pegawai') }}" class="btn btn-danger btn-sm ms-2">Back</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="p-4">
                    <form method="post" action="{{ url('/pegawai/shift/proses-tambah-shift') }}">
                        @csrf
                            <div class="form-group">
                                <label for="shift_id" class="float-left">Shift</label>
                                <select class="form-control selectpicker @error('shift_id') is-invalid @enderror" id="shift_id" name="shift_id" data-live-search="true">
                                    <option value="">Pilih Shift</option>
                                    @foreach ($shift as $s)
                                        @if(old('shift_id') == $s->id)
                                            <option value="{{ $s->id }}" selected>{{ $s->nama_shift . " (" . $s->jam_masuk . " - " . $s->jam_keluar . ") " }}</option>
                                        @else
                                            <option value="{{ $s->id }}">{{ $s->nama_shift . " (" . $s->jam_masuk . " - " . $s->jam_keluar . ") " }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('shift_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="tanggal_mulai" class="float-left">Tanggal Mulai</label>
                                <input type="datetime" class="form-control @error('tanggal_mulai') is-invalid @enderror" id="tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}">
                                @error('tanggal_mulai')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="tanggal_akhir" class="float-left">Tanggal Akhir</label>
                                <input type="datetime" class="form-control @error('tanggal_akhir') is-invalid @enderror" id="tanggal_akhir" name="tanggal_akhir" value="{{ old('tanggal_akhir') }}">
                                @error('tanggal_akhir')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <input type="hidden" name="tanggal">
                                <input type="hidden" name="status_absen">
                            <input type="hidden" name="user_id" value="{{ $karyawan->id }}">
                            </div>
                            <div class="form-check mb-4">
                                <input name="lock_location" class="form-check-input lock_location" type="checkbox" value="{{ old('lock_location') }}" id="lock_location">
                                <label class="form-check-label" for="lock_location">
                                    Lock Location
                                </label>
                            </div>
                        <button type="submit" class="btn btn-primary float-right">Submit</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <center>
                        <h3>{{ $karyawan->name }}</h3>
                    </center>
                    <hr>
                    <form action="{{ url('/pegawai/shift/'.$karyawan->id) }}">
                        <div class="row">
                            <div class="col-3">
                                <input type="datetime" class="form-control" name="tanggal" placeholder="Tanggal" id="tanggal" value="{{ request('tanggal') }}">
                            </div>
                            <div class="col">
                                <button type="submit" id="search"class="border-0 mt-3" style="background-color: transparent;"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <form id="bulkEditForm" method="post" action="{{ url('/pegawai/bulk-edit-shift') }}">
                        @csrf
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAll">
                                <label class="form-check-label" for="selectAll">
                                    Pilih Semua
                                </label>
                            </div>
                            <button type="submit" class="btn btn-warning btn-sm" id="bulkEditBtn" disabled>
                                <i class="fa fa-edit me-1"></i> Bulk Edit (<span id="selectedCount">0</span> dipilih)
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table id="mytable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th width="40"><i class="fa fa-check"></i></th>
                                        <th>No.</th>
                                        <th>Tanggal</th>
                                        <th>Shift Pegawai</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Keluar</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($shift_karyawan as $key => $sk)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input shift-checkbox" name="shift_ids[]" value="{{ $sk->id }}">
                                            </td>
                                            <td>{{ ($shift_karyawan->currentpage() - 1) * $shift_karyawan->perpage() + $key + 1 }}.</td>
                                            <td>{{ $sk->tanggal ?? '-' }}</td>
                                            <td>{{ $sk->Shift->nama_shift ?? '-' }}</td>
                                            <td>{{ $sk->Shift->jam_masuk ?? '-' }}</td>
                                            <td>{{ $sk->Shift->jam_keluar ?? '-' }}</td>
                                            <td>
                                                <ul class="action">
                                                    <li class="edit">
                                                        <a href="{{ url('/pegawai/edit-shift/'.$sk->id) }}"><i class="fa fa-solid fa-edit"></i></a>
                                                    </li>
                                                    <li class="delete">
                                                        <button type="button" class="border-0 btn-delete-shift" style="background-color: transparent;" 
                                                            data-id="{{ $sk->id }}" 
                                                            data-user-id="{{ $karyawan->id }}">
                                                            <i class="fa fa-solid fa-trash"></i>
                                                        </button>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
                <div class="d-flex justify-content-end me-4 mb-4">
                    {{ $shift_karyawan->links() }}
                </div>
            </div>
        </div>
    </div>
    @push('script')
        <script>
            $(function(){
                var lock_location = $('#lock_location').val();
                $('#lock_location').prop('checked', lock_location == "1");

                $('body').on('change', '#lock_location', function (event) {
                    if (this.checked) {
                        $('#lock_location').val(1)
                    } else {
                        $('#lock_location').val(null)
                    }
                });

                // Bulk edit - Select All functionality
                $('#selectAll').on('change', function() {
                    $('.shift-checkbox').prop('checked', this.checked);
                    updateSelectedCount();
                });

                // Update count when individual checkbox changes
                $('.shift-checkbox').on('change', function() {
                    updateSelectedCount();
                    // Update select all checkbox state
                    if ($('.shift-checkbox:checked').length === $('.shift-checkbox').length) {
                        $('#selectAll').prop('checked', true);
                    } else {
                        $('#selectAll').prop('checked', false);
                    }
                });

                function updateSelectedCount() {
                    var count = $('.shift-checkbox:checked').length;
                    $('#selectedCount').text(count);
                    if (count > 0) {
                        $('#bulkEditBtn').prop('disabled', false);
                    } else {
                        $('#bulkEditBtn').prop('disabled', true);
                    }
                }

                // Delete shift handler
                $('.btn-delete-shift').on('click', function() {
                    var shiftId = $(this).data('id');
                    var userId = $(this).data('user-id');
                    
                    if (confirm('Apakah Anda yakin ingin menghapus shift ini?')) {
                        // Create and submit form dynamically
                        var form = $('<form>', {
                            'method': 'POST',
                            'action': '/pegawai/delete-shift/' + shiftId
                        });
                        form.append($('<input>', {'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}'}));
                        form.append($('<input>', {'type': 'hidden', 'name': '_method', 'value': 'DELETE'}));
                        form.append($('<input>', {'type': 'hidden', 'name': 'user_id', 'value': userId}));
                        $('body').append(form);
                        form.submit();
                    }
                });
            });
        </script>
    @endpush
@endsection
