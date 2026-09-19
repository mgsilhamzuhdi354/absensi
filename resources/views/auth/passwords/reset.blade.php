@extends('templates.auth')

@section('content')
    <div class="auth-form-layout">
        <section class="auth-card auth-form-card" aria-labelledby="reset-title">
            <a class="auth-back" href="{{ route('login') }}">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Kembali ke halaman masuk
            </a>

            <p class="auth-eyebrow">PEMULIHAN AKUN</p>
            <h1 class="auth-title" id="reset-title">Buat password baru</h1>
            <p class="auth-description">Gunakan password dengan minimal 8 karakter agar akun Anda tetap aman.</p>

            <form action="{{ route('password.update') }}" method="POST" data-auth-form>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="auth-field">
                    <label for="reset-email">Email</label>
                    <input id="reset-email" type="email" name="email"
                        class="auth-input @error('email') is-invalid @enderror"
                        value="{{ old('email', request('email')) }}" autocomplete="email" readonly required
                        @error('email') aria-invalid="true" aria-describedby="reset-email-error" @enderror>
                    @error('email')
                        <p class="auth-error" id="reset-email-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="reset-password">Password baru</label>
                    <div class="auth-password">
                        <input id="reset-password" type="password" name="password"
                            class="auth-input @error('password') is-invalid @enderror"
                            placeholder="Minimal 8 karakter" autocomplete="new-password" minlength="8" required
                            @error('password') aria-invalid="true" aria-describedby="reset-password-error" @enderror>
                        <button type="button" class="auth-password-toggle" data-password-toggle="reset-password"
                            aria-controls="reset-password" aria-label="Tampilkan password" aria-pressed="false">
                            <i class="far fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="auth-error" id="reset-password-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="reset-password-confirmation">Konfirmasi password baru</label>
                    <div class="auth-password">
                        <input id="reset-password-confirmation" type="password" name="password_confirmation"
                            class="auth-input @error('password_confirmation') is-invalid @enderror"
                            placeholder="Ulangi password baru" autocomplete="new-password" minlength="8" required
                            @error('password_confirmation') aria-invalid="true" aria-describedby="reset-password-confirmation-error" @enderror>
                        <button type="button" class="auth-password-toggle" data-password-toggle="reset-password-confirmation"
                            aria-controls="reset-password-confirmation" aria-label="Tampilkan password" aria-pressed="false">
                            <i class="far fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('password_confirmation')
                        <p class="auth-error" id="reset-password-confirmation-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="auth-submit" data-loading-label="Menyimpan password...">
                    <span data-submit-label>Simpan password baru</span>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </button>
            </form>

            <p class="auth-help">Sudah bisa mengakses akun Anda? <a href="{{ route('login') }}">Kembali masuk</a></p>
        </section>
    </div>
@endsection
