@extends('templates.dashboard')
@section('isi')
    <div class="email-wrap">
        <div class="row">
            <div class="col-md-12 project-list">
            <div class="card">
                <div class="row">
                    <div class="col-md-6 mt-2 p-0 d-flex">
                        <h4>{{ $title }}</h4>
                    </div>
                    <div class="col-md-6 p-0">    
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
          <div class="email-right-aside">
            <div class="card email-body">
              <div class="email-profile">
                <div>
                  <div class="pe-0 b-r-light"></div>
                  <div class="email-top">
                    <div class="row">
                      <div class="col-12">
                        <div class="d-flex">
                          <div class="flex-grow-1">                                                                       
                            <div class="dropdown">
                              <button class="btn btn-primary dropdown-toggle" id="dropdownMenuButton" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>

                              <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <a class="dropdown-item" href="{{ url('/notifications/read') }}">Read</a>
                                <a class="dropdown-item" href="{{ url('/notifications/unread') }}">Unread</a>
                                <div class="dropdown-divider"></div>
                                <form action="{{ url('/notifications/delete-read') }}" method="POST" style="display: inline;" onsubmit="return confirm('Hapus semua notifikasi yang sudah dibaca?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-warning">
                                        <i class="fa fa-trash-alt me-1"></i> Hapus Yang Sudah Dibaca
                                    </button>
                                </form>
                                <form action="{{ url('/notifications/delete-all') }}" method="POST" style="display: inline;" onsubmit="return confirm('Hapus SEMUA notifikasi? Tindakan ini tidak dapat dibatalkan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fa fa-trash me-1"></i> Hapus Semua
                                    </button>
                                </form>
                            </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="inbox">
                    @foreach ($inboxs as $inbox)
                        @php
                            $user = App\Models\User::find($inbox->data['user_id']);
                        @endphp
                        <div class="d-flex align-items-center" style="{{ !$inbox->read_at ? 'background-color: rgb(241, 241, 241)' : '' }}">
                            <a href="{!! !$inbox->read_at ? url('/notifications/read-message/'.$inbox->id) : url($inbox->data['action']); !!}" class="d-flex flex-grow-1" style="text-decoration: none; color: inherit;">
                                <div class="d-flex-size-email">                                       
                                    <label class="d-block mb-0">
                                    @if ($user->foto_karyawan == null)
                                        <img class="me-3 rounded-circle" src="{{ url('assets/img/foto_default.jpg') }}" alt="image">
                                    @else
                                        <img class="me-3 rounded-circle" src="{{ url('/storage/'.$user->foto_karyawan) }}" alt="">
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <h6>{{ $user->name }} </h6>
                                    <p>{{ $inbox->data['message'] }}</p><span>{{ date('d M Y H:i:s',strtotime($inbox->created_at)) }}</span>
                                </div>
                            </a>
                            <form action="{{ url('/notifications/delete/'.$inbox->id) }}" method="POST" style="padding: 10px;" onsubmit="return confirm('Hapus notifikasi ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                    <i class="fa fa-times"></i>
                                </button>
                            </form>
                        </div>
                    @endforeach
                    <div class="d-flex justify-content-end me-4 mt-4">
                        {{ $inboxs->links() }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    
@endsection

