@php
    $variants = collect($variants ?? [])->filter(fn ($variant) => (float) ($variant->stok ?? 0) > 0);
    $unit = $unit ?? '';
@endphp

@if ($variants->isNotEmpty())
    <div class="stock-color-summary d-flex flex-wrap gap-1 justify-content-center">
        @foreach ($variants as $variant)
            <span class="badge bg-light text-dark border">
                {{ $variant->warna_barang }}: {{ $variant->formatted_stock ?? $variant->stok }} {{ $unit }}
            </span>
        @endforeach
    </div>
@else
    <span class="text-muted">Belum ada warna</span>
@endif
