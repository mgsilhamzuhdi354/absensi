@php
    $loginCompanies = $companies ?? collect();
    $activeCompanyId = (int) old('company_id', $selectedCompanyId ?? optional($loginCompanies->first())->id);
    if ($loginCompanies->isNotEmpty() && !$loginCompanies->contains('id', $activeCompanyId)) {
        $activeCompanyId = (int) $loginCompanies->first()->id;
    }
    $activeCompany = $loginCompanies->firstWhere('id', $activeCompanyId);
    $companyWasSelected = request()->filled('company_id') && $activeCompany;
@endphp
<section class="auth-card auth-login-card" aria-labelledby="login-title">
    <a href="{{ route('welcome', $activeCompanyId ? ['company_id' => $activeCompanyId] : []) }}" class="auth-back"><i class="fas fa-arrow-left" aria-hidden="true"></i> Kembali ke beranda</a>
    <span class="auth-eyebrow">{{ ($adminLogin ?? false) ? 'AKSES ADMIN' : 'PORTAL KARYAWAN' }}</span>
    <h1 class="auth-title" id="login-title">Senang Anda kembali.</h1>
    <p class="auth-description">Masuk untuk mengakses {{ ($adminLogin ?? false) ? 'panel administrasi' : 'dashboard dan aktivitas kerja' }} Anda.</p>
    <form action="{{ url('/login-proses') }}" method="POST" data-auth-form>
        @csrf
        @if($loginCompanies->isNotEmpty())
            @if($companyWasSelected)
                <input type="hidden" name="company_id" id="company_id" value="{{ $activeCompanyId }}">
                <div class="auth-selected-company auth-login-company">
                    <span class="auth-company-icon"><i class="far fa-building" aria-hidden="true"></i></span>
                    <span><small>Perusahaan aktif</small><strong>{{ $activeCompany->name }}</strong></span>
                    <a href="{{ route('welcome') }}">Ganti<span class="auth-sr-only"> perusahaan</span></a>
                </div>
                @error('company_id')<p class="auth-error auth-company-error" id="company-error">{{ $message }}</p>@enderror
            @else
                <div class="auth-field">
                    <label for="company_id">Perusahaan</label>
                    <select class="auth-input" name="company_id" id="company_id" required aria-describedby="company-hint{{ $errors->has('company_id') ? ' company-error' : '' }}" @error('company_id') aria-invalid="true" @enderror>
                        @foreach($loginCompanies as $company)
                            <option value="{{ $company->id }}" {{ $activeCompanyId === (int) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                    <p class="auth-field-hint" id="company-hint">Pilih perusahaan jika Anda membuka halaman masuk secara langsung.</p>
                    @error('company_id')<p class="auth-error" id="company-error">{{ $message }}</p>@enderror
                </div>
            @endif
        @endif
        <div class="auth-field">
            <label for="username">Username / NIK</label>
            <input class="auth-input" id="username" type="text" name="username" value="{{ old('username') }}" placeholder="Masukkan username atau NIK" autocomplete="username" autocapitalize="none" spellcheck="false" required @error('username') aria-invalid="true" aria-describedby="username-error" @enderror>
            @error('username')<p class="auth-error" id="username-error">{{ $message }}</p>@enderror
        </div>
        <div class="auth-field">
            <div class="auth-label-row"><label for="password-field">Password</label><a href="{{ url('/forgot-password') }}">Lupa password?</a></div>
            <div class="auth-password">
                <input class="auth-input" type="password" name="password" id="password-field" placeholder="Masukkan password" autocomplete="current-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <button type="button" class="auth-password-toggle" data-password-toggle="password-field" aria-controls="password-field" aria-label="Tampilkan password" aria-pressed="false"><i class="far fa-eye" aria-hidden="true"></i></button>
            </div>
            @error('password')<p class="auth-error" id="password-error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="auth-submit" data-loading-label="Sedang masuk..."><span data-submit-label>Masuk{{ ($adminLogin ?? false) ? ' sebagai Admin' : '' }}</span><i class="fas fa-arrow-right" aria-hidden="true"></i></button>
    </form>
    <div class="auth-login-help"><i class="far fa-question-circle" aria-hidden="true"></i><span>Belum punya akun? Hubungi admin perusahaan Anda.</span></div>
</section>
