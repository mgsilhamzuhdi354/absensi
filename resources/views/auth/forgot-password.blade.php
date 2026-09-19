@extends('templates.auth')

@section('content')
    <div class="auth-form-layout">
        <section class="auth-card auth-form-card" aria-labelledby="forgot-title">
            <a class="auth-back" href="{{ route('login') }}">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Kembali ke halaman masuk
            </a>

            <p class="auth-eyebrow">PEMULIHAN AKUN</p>
            <h1 class="auth-title" id="forgot-title">Lupa password?</h1>
            <p class="auth-description">Masukkan email yang terdaftar. Kami akan mengirimkan tautan untuk membuat password baru.</p>

            <form action="{{ url('/forgot-password/link') }}" method="POST" data-auth-form>
                @csrf

                <div class="auth-field">
                    <label for="recovery-email">Email</label>
                    <input id="recovery-email" type="email" name="email"
                        class="auth-input @error('email') is-invalid @enderror"
                        value="{{ old('email') }}" placeholder="nama@email.com"
                        autocomplete="email" required
                        @error('email') aria-invalid="true" aria-describedby="recovery-email-error" @enderror>
                    @error('email')
                        <p class="auth-error" id="recovery-email-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="auth-submit" data-loading-label="Mengirim tautan...">
                    <span data-submit-label>Kirim tautan pemulihan</span>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </button>
            </form>

            <p class="auth-help">Sudah ingat password Anda? <a href="{{ route('login') }}">Masuk sekarang</a></p>
        </section>
    </div>
@endsection
