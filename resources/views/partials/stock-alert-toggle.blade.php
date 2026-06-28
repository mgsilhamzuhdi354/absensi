@php
    $enabled = (bool) ($enabled ?? true);
    $id = $id ?? ('stock_alert_' . uniqid());
@endphp

<form method="post" action="{{ $action }}" class="stock-alert-toggle-form d-inline-block">
    @csrf
    @method('put')
    <input type="hidden" name="stock_alert_enabled" value="0">
    <div class="form-check form-switch m-0 d-inline-flex align-items-center gap-1">
        <input
            class="form-check-input js-stock-alert-toggle"
            type="checkbox"
            id="{{ $id }}"
            name="stock_alert_enabled"
            value="1"
            {{ $enabled ? 'checked' : '' }}
            onchange="this.form.submit()"
        >
        <label class="form-check-label small mb-0" for="{{ $id }}">{{ $enabled ? 'Notif aktif' : 'Notif mati' }}</label>
    </div>
</form>
