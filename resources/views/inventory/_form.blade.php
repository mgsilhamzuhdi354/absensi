@php
    $inventory = $inventory ?? null;
    $submitLabel = $submitLabel ?? 'Submit';
    $kondisiOptions = ['Baru', 'Baik', 'Normal', 'Rusak Ringan', 'Rusak Berat'];
    $statusOptions = ['Aktif', 'Disimpan', 'Dipakai', 'Maintenance', 'Rusak', 'Hilang'];
    $stockInputValue = old('stok', $inventory ? $inventory->formatted_stock : '');
    $stockInputStep = (!$inventory || $inventory->usesWholeStock()) ? '1' : '0.01';
@endphp

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="kode_barang" class="float-left">Kode Barang</label>
            <input type="text" class="form-control @error('kode_barang') is-invalid @enderror" id="kode_barang" name="kode_barang" value="{{ $inventory->kode_barang ?? $kode_barang ?? '' }}" readonly>
            @error('kode_barang')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="nama_barang" class="float-left">Nama Barang</label>
            <input type="text" class="form-control @error('nama_barang') is-invalid @enderror" id="nama_barang" name="nama_barang" value="{{ old('nama_barang', $inventory->nama_barang ?? '') }}">
            @error('nama_barang')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="jenis_barang" class="float-left">Jenis Barang</label>
            <input type="text" class="form-control @error('jenis_barang') is-invalid @enderror" id="jenis_barang" name="jenis_barang" value="{{ old('jenis_barang', $inventory->jenis_barang ?? '') }}">
            @error('jenis_barang')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="merk_tipe" class="float-left">Merk / Tipe</label>
            <input type="text" class="form-control @error('merk_tipe') is-invalid @enderror" id="merk_tipe" name="merk_tipe" value="{{ old('merk_tipe', $inventory->merk_tipe ?? '') }}">
            @error('merk_tipe')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="serial_number" class="float-left">Serial Number</label>
            <input type="text" class="form-control @error('serial_number') is-invalid @enderror" id="serial_number" name="serial_number" value="{{ old('serial_number', $inventory->serial_number ?? '') }}">
            @error('serial_number')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="stok" class="float-left">Stok</label>
            <input type="number" step="{{ $stockInputStep }}" min="0" class="form-control @error('stok') is-invalid @enderror" id="stok" name="stok" value="{{ $stockInputValue }}">
            @error('stok')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="uom" class="float-left">UoM</label>
            <input type="text" class="form-control @error('uom') is-invalid @enderror" id="uom" name="uom" list="uom_options" value="{{ old('uom', $inventory->uom ?? 'Unit') }}">
            <datalist id="uom_options">
                <option value="Unit">
                <option value="Pcs">
                <option value="Set">
                <option value="Box">
                <option value="Pack">
                <option value="Kg">
                <option value="Liter">
                <option value="Meter">
                <option value="Roll">
            </datalist>
            @error('uom')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="kondisi" class="float-left">Kondisi</label>
            <select class="form-control @error('kondisi') is-invalid @enderror" id="kondisi" name="kondisi">
                <option value="">-- Pilih --</option>
                @foreach ($kondisiOptions as $option)
                    <option value="{{ $option }}" {{ old('kondisi', $inventory->kondisi ?? '') == $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
            @error('kondisi')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="status_barang" class="float-left">Status Barang</label>
            <select class="form-control @error('status_barang') is-invalid @enderror" id="status_barang" name="status_barang">
                <option value="">-- Pilih --</option>
                @foreach ($statusOptions as $option)
                    <option value="{{ $option }}" {{ old('status_barang', $inventory->status_barang ?? '') == $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
            @error('status_barang')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="tanggal_masuk" class="float-left">Tanggal Masuk</label>
            <input type="date" class="form-control @error('tanggal_masuk') is-invalid @enderror" id="tanggal_masuk" name="tanggal_masuk" value="{{ old('tanggal_masuk', optional($inventory->tanggal_masuk ?? null)->format('Y-m-d')) }}">
            @error('tanggal_masuk')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="lokasi_id" class="float-left">Lokasi</label>
            <select class="form-control selectpicker @error('lokasi_id') is-invalid @enderror" id="lokasi_id" name="lokasi_id" data-live-search="true">
                <option value="">-- Pilih --</option>
                @foreach ($lokasi as $lok)
                    <option value="{{ $lok->id }}" {{ old('lokasi_id', $inventory->lokasi_id ?? '') == $lok->id ? 'selected' : '' }}>{{ $lok->nama_lokasi }}</option>
                @endforeach
            </select>
            @error('lokasi_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="jabatan_id" class="float-left">Divisi / Jabatan</label>
            <select class="form-control selectpicker @error('jabatan_id') is-invalid @enderror" id="jabatan_id" name="jabatan_id" data-live-search="true">
                <option value="">-- Pilih --</option>
                @foreach ($jabatan as $jab)
                    <option value="{{ $jab->id }}" {{ old('jabatan_id', $inventory->jabatan_id ?? '') == $jab->id ? 'selected' : '' }}>{{ $jab->nama_jabatan }}</option>
                @endforeach
            </select>
            @error('jabatan_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label for="spesifikasi" class="form-label">Spesifikasi</label>
    <textarea name="spesifikasi" id="spesifikasi" class="form-control @error('spesifikasi') is-invalid @enderror" rows="4">{{ old('spesifikasi', $inventory->spesifikasi ?? '') }}</textarea>
    @error('spesifikasi')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="desc" class="form-label">Description</label>
    <textarea name="desc" id="desc" class="form-control @error('desc') is-invalid @enderror" rows="4">{{ old('desc', $inventory->desc ?? '') }}</textarea>
    @error('desc')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="foto_barang" class="float-left">Foto Barang</label>
    <input type="file" class="form-control @error('foto_barang') is-invalid @enderror" id="foto_barang" name="foto_barang" accept="image/*">
    @error('foto_barang')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    @if ($inventory && $inventory->foto_barang)
        <div class="mt-2">
            <img src="{{ asset('storage/' . $inventory->foto_barang) }}" alt="{{ $inventory->nama_barang }}" class="img-fluid rounded border" style="max-height: 180px; object-fit: cover;">
        </div>
        <a href="{{ asset('storage/' . $inventory->foto_barang) }}" target="_blank" class="d-inline-block mt-2">Lihat foto saat ini</a>
    @endif
</div>

<button type="submit" class="btn btn-primary float-right">{{ $submitLabel }}</button>

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var uomInput = document.getElementById('uom');
            var stockInput = document.getElementById('stok');
            var wholeUoms = ['unit', 'pcs', 'pc', 'piece', 'pieces', 'set', 'box', 'pack', 'buah'];

            if (!uomInput || !stockInput) {
                return;
            }

            function normalizeUom(value) {
                return String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, '');
            }

            function syncStockStep() {
                var isWholeStock = wholeUoms.indexOf(normalizeUom(uomInput.value)) !== -1;
                stockInput.step = isWholeStock ? '1' : '0.01';

                if (isWholeStock && stockInput.value !== '') {
                    var numericValue = Number(stockInput.value);
                    if (!Number.isNaN(numericValue)) {
                        stockInput.value = Math.round(numericValue);
                    }
                }
            }

            uomInput.addEventListener('input', syncStockStep);
            uomInput.addEventListener('change', syncStockStep);
            syncStockStep();
        });
    </script>
@endpush
