<?php

namespace App\Services;

use App\Models\User;
use App\Models\settings;
use Carbon\Carbon;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeQrService
{
    public const MODE_PROFILE = 'profile';
    public const MODE_VCARD = 'vcard';

    private const PUBLIC_DISK = 'public';
    private const DIRECTORY = 'employee/qrcodes';

    public static function allowedFields(): array
    {
        return [
            'foto' => 'Foto',
            'name' => 'Nama',
            'employee_id' => 'Employee ID',
            'jabatan' => 'Jabatan / Divisi',
            'lokasi' => 'Lokasi',
            'telepon' => 'Telepon',
            'email' => 'Email',
            'tgl_join' => 'Tanggal Masuk',
            'alamat' => 'Alamat',
            'emergency_contact' => 'Kontak Darurat',
            'custom_info' => 'Informasi Tambahan',
        ];
    }

    public static function defaultVisibleFields(): array
    {
        return ['foto', 'name', 'employee_id', 'jabatan', 'lokasi', 'telepon', 'email', 'custom_info'];
    }

    public static function customInfoIconCatalog(): array
    {
        return [
            'info-circle' => 'Info',
            'briefcase' => 'Pekerjaan',
            'building' => 'Gedung',
            'phone' => 'Telepon',
            'envelope' => 'Email',
            'id-card' => 'ID Card',
            'calendar-alt' => 'Kalender',
            'map-marker-alt' => 'Lokasi',
            'user-shield' => 'Kontak Darurat',
            'heart' => 'Kesehatan',
            'medkit' => 'Medis',
            'car' => 'Kendaraan',
            'clock' => 'Waktu',
            'star' => 'Penting',
            'tag' => 'Tag',
            'home' => 'Rumah',
            'globe' => 'Website',
            'graduation-cap' => 'Pendidikan',
            'certificate' => 'Sertifikat',
            'users' => 'Tim',
        ];
    }

    public function ensure(User $user, bool $force = false): User
    {
        if (!$user->exists) {
            return $user;
        }

        if (!$user->employee_qr_token || $this->isLegacyLongToken($user->employee_qr_token)) {
            $user->employee_qr_token = $this->newShortToken();
            $force = true;
        }

        $setting = settings::first();
        $profileValue = $this->profileValueFor($user);
        $vcardValue = $this->vcardQrValueFor($user);
        $profilePath = $this->imagePath($user, self::MODE_PROFILE);
        $vcardPath = $this->imagePath($user, self::MODE_VCARD);

        if ($this->needsImage($user->employee_qr_profile_value, $user->employee_qr_profile_image, $profileValue, $profilePath, $force)) {
            $this->writeQrImage($profileValue, $profilePath);
        }

        if ($this->needsImage($user->employee_qr_vcard_value, $user->employee_qr_vcard_image, $vcardValue, $vcardPath, $force)) {
            $this->writeQrImage($vcardValue, $vcardPath);
        }

        $user->forceFill([
            'employee_qr_token' => $user->employee_qr_token,
            'employee_qr_profile_value' => $profileValue,
            'employee_qr_profile_image' => $profilePath,
            'employee_qr_vcard_value' => $vcardValue,
            'employee_qr_vcard_image' => $vcardPath,
        ])->save();

        return $user->fresh(['Jabatan', 'Lokasi']);
    }

    public function regenerate(User $user): User
    {
        $user->forceFill([
            'employee_qr_token' => $this->newShortToken(),
            'employee_qr_profile_value' => null,
            'employee_qr_vcard_value' => null,
        ])->save();

        return $this->ensure($user->fresh(['Jabatan', 'Lokasi']), true);
    }

    public function profileValueFor(User $user): string
    {
        return url('/e/' . $user->employee_qr_token);
    }

    public function vcardQrValueFor(User $user): string
    {
        return url('/e/' . $user->employee_qr_token . '/v');
    }

    public function vcardFor(User $user, ?settings $setting = null): string
    {
        $setting = $setting ?: settings::first();
        $visible = $this->visibleFields($setting);
        $companyName = $setting->name ?? config('app.name', 'HRIS');
        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'FN:' . $this->escapeVcard($user->name ?: $user->username ?: 'Karyawan'),
            'N:' . $this->escapeVcard($user->name ?: $user->username ?: 'Karyawan') . ';;;;',
            'ORG:' . $this->escapeVcard($companyName),
        ];

        if (in_array('jabatan', $visible, true) && optional($user->Jabatan)->nama_jabatan) {
            $lines[] = 'TITLE:' . $this->escapeVcard($user->Jabatan->nama_jabatan);
        }

        if (in_array('telepon', $visible, true) && $user->telepon) {
            $lines[] = 'TEL;TYPE=CELL:' . $this->escapeVcard($user->telepon);
        }

        if (in_array('email', $visible, true) && $user->email) {
            $lines[] = 'EMAIL;TYPE=WORK:' . $this->escapeVcard($user->email);
        }

        if (in_array('alamat', $visible, true) && $user->alamat) {
            $lines[] = 'ADR;TYPE=WORK:;;' . $this->escapeVcard($user->alamat) . ';;;;';
        }

        if (in_array('foto', $visible, true) && $user->foto_karyawan) {
            $lines[] = 'PHOTO;VALUE=URI:' . asset('storage/' . $user->foto_karyawan);
        }

        $lines[] = 'URL:' . $this->profileValueFor($user);
        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines) . "\r\n";
    }

    public function visibleFields(?settings $setting = null): array
    {
        $setting = $setting ?: settings::first();
        $configured = json_decode($setting->employee_qr_visible_fields ?? '', true);

        if (!is_array($configured) || empty($configured)) {
            $configured = self::defaultVisibleFields();
        }

        $allowed = array_keys(self::allowedFields());

        return array_values(array_intersect($configured, $allowed));
    }

    public function publicProfileRows(User $user, ?settings $setting = null): array
    {
        $visible = $this->visibleFields($setting);
        $rows = [];

        if (in_array('employee_id', $visible, true) && $user->employee_id) {
            $rows[] = ['label' => 'Employee ID', 'value' => $user->employee_id, 'icon' => 'fa-id-card'];
        }

        if (in_array('jabatan', $visible, true) && optional($user->Jabatan)->nama_jabatan) {
            $rows[] = ['label' => 'Jabatan / Divisi', 'value' => $user->Jabatan->nama_jabatan, 'icon' => 'fa-briefcase'];
        }

        if (in_array('lokasi', $visible, true) && optional($user->Lokasi)->nama_lokasi) {
            $rows[] = ['label' => 'Lokasi', 'value' => $user->Lokasi->nama_lokasi, 'icon' => 'fa-building'];
        }

        if (in_array('telepon', $visible, true) && $user->telepon) {
            $rows[] = ['label' => 'Telepon', 'value' => $user->telepon, 'icon' => 'fa-phone', 'href' => 'tel:' . $user->telepon];
        }

        if (in_array('email', $visible, true) && $user->email) {
            $rows[] = ['label' => 'Email', 'value' => $user->email, 'icon' => 'fa-envelope', 'href' => 'mailto:' . $user->email];
        }

        if (in_array('tgl_join', $visible, true) && $user->tgl_join) {
            $rows[] = ['label' => 'Tanggal Masuk', 'value' => $this->formatDate($user->tgl_join), 'icon' => 'fa-calendar-alt'];
        }

        if (in_array('alamat', $visible, true) && $user->alamat) {
            $rows[] = ['label' => 'Alamat', 'value' => $user->alamat, 'icon' => 'fa-map-marker-alt'];
        }

        if (in_array('emergency_contact', $visible, true) && ($user->nama_kontak_darurat || $user->telepon_kontak_darurat)) {
            $value = trim(implode(' - ', array_filter([
                $user->nama_kontak_darurat,
                $user->hubungan_kontak_darurat,
                $user->telepon_kontak_darurat,
            ])));
            $rows[] = ['label' => 'Kontak Darurat', 'value' => $value, 'icon' => 'fa-user-shield'];
        }

        if (in_array('custom_info', $visible, true)) {
            foreach ($this->customInfoItems($user) as $item) {
                $rows[] = [
                    'label' => $item['label'],
                    'value' => $item['value'],
                    'icon' => 'fa-' . $item['icon'],
                ];
            }
        }

        return $rows;
    }

    public function customInfoItems(User $user): array
    {
        $raw = trim((string) $user->employee_qr_custom_info);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [[
                'label' => 'Informasi Tambahan',
                'value' => $raw,
                'icon' => 'info-circle',
            ]];
        }

        $items = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $value = trim((string) ($item['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            $items[] = [
                'label' => $label !== '' ? $label : 'Informasi',
                'value' => $value,
                'icon' => $this->sanitizeCustomInfoIcon($item['icon'] ?? null),
            ];
        }

        return $items;
    }

    public function sanitizeCustomInfoIcon($icon): string
    {
        $icon = trim((string) $icon);

        return array_key_exists($icon, self::customInfoIconCatalog()) ? $icon : 'info-circle';
    }

    public function isVisible(?settings $setting, string $field): bool
    {
        return in_array($field, $this->visibleFields($setting), true);
    }

    public function imageForMode(User $user, string $mode): ?string
    {
        if ($mode === self::MODE_VCARD) {
            return $user->employee_qr_vcard_image;
        }

        return $user->employee_qr_profile_image;
    }

    public function dataUriForPublicDisk(?string $path): ?string
    {
        if (!$path || !Storage::disk(self::PUBLIC_DISK)->exists($path)) {
            return null;
        }

        $absolutePath = Storage::disk(self::PUBLIC_DISK)->path($path);
        $type = pathinfo($absolutePath, PATHINFO_EXTENSION) ?: 'png';

        return 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($absolutePath));
    }

    public function dataUriForAbsolutePath(?string $path): ?string
    {
        if (!$path || !file_exists($path)) {
            return null;
        }

        $type = pathinfo($path, PATHINFO_EXTENSION) ?: 'png';

        return 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($path));
    }

    public function photoUrl(User $user): string
    {
        if ($user->foto_karyawan) {
            return asset('storage/' . $user->foto_karyawan);
        }

        return asset('assets/img/foto_default.jpg');
    }

    public function photoDataUri(User $user): ?string
    {
        if ($user->foto_karyawan) {
            return $this->dataUriForAbsolutePath(storage_path('app/public/' . $user->foto_karyawan));
        }

        return $this->dataUriForAbsolutePath(public_path('assets/img/foto_default.jpg'));
    }

    public function logoUrl(?settings $setting = null): string
    {
        $setting = $setting ?: settings::first();

        if ($setting && $setting->logo && file_exists(storage_path('app/public/' . $setting->logo))) {
            return asset('storage/' . $setting->logo);
        }

        return asset('assets/img/absensi.png');
    }

    public function logoDataUri(?settings $setting = null): ?string
    {
        return $this->dataUriForAbsolutePath($this->logoPath($setting));
    }

    public function safeFilename($value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/[^A-Za-z0-9._-]+/', '-', $value);
        $value = trim($value, '-');

        return $value !== '' ? $value : 'employee';
    }

    private function needsImage(?string $currentValue, ?string $currentPath, string $newValue, string $newPath, bool $force): bool
    {
        return $force
            || $currentValue !== $newValue
            || $currentPath !== $newPath
            || !Storage::disk(self::PUBLIC_DISK)->exists($newPath);
    }

    private function writeQrImage(string $value, string $path): void
    {
        $builder = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($value)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelMedium())
            ->size(480)
            ->margin(12)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->validateResult(false);

        $logoPath = $this->logoPath(settings::first());
        if ($logoPath && $this->isRasterImage($logoPath)) {
            $builder
                ->logoPath($logoPath)
                ->logoResizeToWidth(64)
                ->logoResizeToHeight(64)
                ->logoPunchoutBackground(true);
        }

        Storage::disk(self::PUBLIC_DISK)->put($path, $builder->build()->getString());
    }

    private function newShortToken(): string
    {
        do {
            $token = Str::lower(Str::random(4));
        } while (User::where('employee_qr_token', $token)->exists());

        return $token;
    }

    private function isLegacyLongToken(?string $token): bool
    {
        if (!$token) {
            return true;
        }

        return strlen($token) > 4 || preg_match('/^[a-f0-9-]{32,}$/i', $token);
    }

    private function imagePath(User $user, string $mode): string
    {
        return self::DIRECTORY . '/' . $mode . '/' . $user->id . '.png';
    }

    private function logoPath(?settings $setting = null): ?string
    {
        $setting = $setting ?: settings::first();
        $candidates = [];

        if ($setting && $setting->logo) {
            $candidates[] = storage_path('app/public/' . $setting->logo);
            $candidates[] = storage_path('app/' . $setting->logo);
        }

        $candidates[] = public_path('assets/img/absensi.png');
        $candidates[] = public_path('images/logo.png');

        foreach ($candidates as $candidate) {
            if ($candidate && file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function isRasterImage(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true);
    }

    private function escapeVcard($value): string
    {
        $value = (string) $value;
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace(["\r\n", "\r", "\n"], '\\n', $value);
        $value = str_replace([';', ','], ['\\;', '\\,'], $value);

        return $value;
    }

    private function formatDate($value): string
    {
        try {
            return Carbon::parse($value)->format('d M Y');
        } catch (\Exception $e) {
            return (string) $value;
        }
    }
}
