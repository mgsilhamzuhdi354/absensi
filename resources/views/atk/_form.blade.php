@php
    $atk = $atk ?? null;
    $submitLabel = $submitLabel ?? 'Submit';
    $isActive = old('active', $atk ? $atk->active : 1);
    $stockAlertEnabled = old('stock_alert_enabled', $atk ? (int) ($atk->stock_alert_enabled ?? true) : 1);
    $warnaOptions = $atk && $atk->relationLoaded('stockVariants') ? $atk->stockVariants : collect();
    $selectedCompanyId = old('company_id', $atk->company_id ?? $companyId ?? optional($currentCompany ?? null)->id);
@endphp

<div class="row">
    @if (auth()->user()->is_admin == 'admin')
        <div class="col-md-6">
            <div class="form-group">
                <label for="company_id" class="float-left">Perusahaan</label>
                <select class="form-control @error('company_id') is-invalid @enderror" id="company_id" name="company_id" {{ $atk ? 'disabled' : '' }}>
                    <option value="">-- Pilih --</option>
                    @foreach (($companies ?? collect()) as $company)
                        <option value="{{ $company->id }}" data-code-preview="{{ ($companyCodePreviews ?? collect())->get($company->id) }}" {{ (string) $selectedCompanyId === (string) $company->id ? 'selected' : '' }}>{{ $company->code }} - {{ $company->name }}</option>
                    @endforeach
                </select>
                @if ($atk)
                    <input type="hidden" name="company_id" value="{{ $atk->company_id }}">
                @endif
                @error('company_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endif
    <div class="col-md-6">
        <div class="form-group">
            <label for="kode_atk" class="float-left">Kode ATK</label>
            <input type="text" class="form-control @error('kode_atk') is-invalid @enderror" id="kode_atk" name="kode_atk" value="{{ $atk->kode_atk ?? $kode_atk ?? '' }}" readonly>
            @error('kode_atk')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="nama_atk" class="float-left">Nama ATK</label>
            <input type="text" class="form-control @error('nama_atk') is-invalid @enderror" id="nama_atk" name="nama_atk" value="{{ old('nama_atk', $atk->nama_atk ?? '') }}">
            @error('nama_atk')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="kategori" class="float-left">Kategori</label>
            <input type="text" class="form-control @error('kategori') is-invalid @enderror" id="kategori" name="kategori" value="{{ old('kategori', $atk->kategori ?? '') }}">
            @error('kategori')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="stok" class="float-left">Stok</label>
            <input type="number" step="0.01" min="0" class="form-control @error('stok') is-invalid @enderror" id="stok" name="stok" value="{{ old('stok', $atk ? $atk->formatted_stock : '') }}">
            @error('stok')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="satuan" class="float-left">Satuan</label>
            <input type="text" class="form-control @error('satuan') is-invalid @enderror" id="satuan" name="satuan" list="satuan_options" value="{{ old('satuan', $atk->satuan ?? 'Pcs') }}">
            <datalist id="satuan_options">
                <option value="Pcs">
                <option value="Box">
                <option value="Pack">
                <option value="Rim">
                <option value="Lusin">
                <option value="Unit">
                <option value="Roll">
            </datalist>
            @error('satuan')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label for="warna_barang" class="float-left">Warna Stok Awal / Penyesuaian</label>
    <input type="text" class="form-control @error('warna_barang') is-invalid @enderror" id="warna_barang" name="warna_barang" list="warna_atk_options" value="{{ old('warna_barang', 'Umum') }}" placeholder="Contoh: Merah, Biru, Hitam">
    <datalist id="warna_atk_options">
        <option value="Umum">
        <option value="Merah">
        <option value="Hitam">
        <option value="Biru">
        <option value="Putih">
        <option value="Hijau">
        @foreach ($warnaOptions as $variant)
            <option value="{{ $variant->warna_barang }}">
        @endforeach
    </datalist>
    @error('warna_barang')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="lokasi" class="float-left">Lokasi</label>
    <input type="text" class="form-control @error('lokasi') is-invalid @enderror" id="lokasi" name="lokasi" value="{{ old('lokasi', $atk->lokasi ?? '') }}">
    @error('lokasi')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="keterangan" class="form-label">Keterangan</label>
    <textarea name="keterangan" id="keterangan" class="form-control @error('keterangan') is-invalid @enderror" rows="4">{{ old('keterangan', $atk->keterangan ?? '') }}</textarea>
    @error('keterangan')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="foto_barang" class="float-left">Foto Barang</label>
    @if ($atk && $atk->foto_barang)
        <div class="mb-2">
            <img src="{{ asset('storage/'.$atk->foto_barang) }}" alt="{{ $atk->nama_atk }}" class="img-fluid rounded" style="max-width: 180px; max-height: 140px; object-fit: cover;">
        </div>
    @endif
    <input type="file" class="form-control @error('foto_barang') is-invalid @enderror" id="foto_barang" name="foto_barang" accept="image/jpeg,image/png,image/webp">
    @error('foto_barang')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <input type="hidden" name="active" value="0">
    <input name="active" class="form-check-input" type="checkbox" value="1" id="active" {{ (string) $isActive === '1' ? 'checked' : '' }}>
    <label class="form-check-label" for="active">Aktif</label>
</div>

<div class="form-group">
    <input type="hidden" name="stock_alert_enabled" value="0">
    <input name="stock_alert_enabled" class="form-check-input" type="checkbox" value="1" id="stock_alert_enabled" {{ (string) $stockAlertEnabled === '1' ? 'checked' : '' }}>
    <label class="form-check-label" for="stock_alert_enabled">Notifikasi stok menipis/habis</label>
</div>

<button type="submit" class="btn btn-primary float-right">{{ $submitLabel }}</button>

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var companySelect = document.getElementById('company_id');
            var codeInput = document.getElementById('kode_atk');

            function syncCompanyCodePreview() {
                if (!companySelect || !codeInput || companySelect.disabled) {
                    return;
                }

                var selectedOption = companySelect.options[companySelect.selectedIndex];
                var preview = selectedOption ? selectedOption.getAttribute('data-code-preview') : '';

                if (preview) {
                    codeInput.value = preview;
                }
            }

            if (companySelect && codeInput) {
                companySelect.addEventListener('change', syncCompanyCodePreview);

                if (window.jQuery) {
                    window.jQuery(companySelect).on('changed.bs.select change', syncCompanyCodePreview);
                }

                syncCompanyCodePreview();
            }
        });
    </script>
@endpush
