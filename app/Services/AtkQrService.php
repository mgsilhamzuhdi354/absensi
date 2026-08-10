<?php

namespace App\Services;

use App\Models\Atk;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AtkQrService
{
    private const PUBLIC_DISK = 'public';
    private const DIRECTORY = 'atk/qrcodes';

    public function ensure(Atk $atk, $force = false)
    {
        if (!$atk->exists) {
            return $atk;
        }

        if (!$atk->qr_token) {
            $atk->qr_token = (string) Str::uuid();
        }

        $qrValue = $this->valueFor($atk);
        $qrPath = self::DIRECTORY . '/' . $atk->id . '.png';
        $needsImage = $force
            || $atk->qr_code_value !== $qrValue
            || $atk->qr_code_image !== $qrPath
            || !Storage::disk(self::PUBLIC_DISK)->exists($qrPath);

        if ($needsImage) {
            $result = Builder::create()
                ->writer(new PngWriter())
                ->writerOptions([])
                ->data($qrValue)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->size(300)
                ->margin(10)
                ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->validateResult(false)
                ->build();

            Storage::disk(self::PUBLIC_DISK)->put($qrPath, $result->getString());
        }

        $atk->forceFill([
            'qr_code_value' => $qrValue,
            'qr_code_image' => $qrPath,
            'qr_token' => $atk->qr_token,
        ])->save();

        return Atk::withoutGlobalScope('company')->find($atk->id);
    }

    public function valueFor(Atk $atk)
    {
        return url('/atk/scan/lookup?code=' . urlencode($atk->qr_token));
    }

    public function findByInput($value)
    {
        $candidates = $this->extractCandidates($value);
        if (empty($candidates)) {
            return null;
        }

        foreach ($candidates as $candidate) {
            $atk = $this->findByCandidate($candidate);

            if ($atk) {
                return $atk;
            }
        }

        return null;
    }

    private function findByCandidate($candidate)
    {
        if (preg_match('/^id:([0-9]+)$/', $candidate, $matches)) {
            return Atk::find((int) $matches[1]);
        }

        return Atk::where('qr_token', $candidate)
            ->orWhere('qr_code_value', $candidate)
            ->orWhere('kode_atk', $candidate)
            ->first();
    }

    private function extractCandidates($value): array
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }

        $raw = preg_replace('/[\x00-\x1F\x7F\x{200B}-\x{200D}\x{FEFF}]/u', '', $raw);
        $candidates = [$raw];

        if (ctype_digit($raw)) {
            $candidates[] = 'id:' . $raw;
        }

        $raw = $this->addUrlDecodedCandidates($raw, $candidates);

        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            $this->addUrlCandidates($raw, $candidates);
        }

        $this->addPatternCandidates($candidates);

        return $this->uniqueCandidates($candidates);
    }

    private function addUrlDecodedCandidates($raw, array &$candidates)
    {
        for ($i = 0; $i < 2; $i++) {
            $decoded = urldecode($raw);

            if ($decoded === $raw) {
                break;
            }

            $candidates[] = trim($decoded);
            $raw = $decoded;
        }

        return $raw;
    }

    private function addUrlCandidates($url, array &$candidates): void
    {
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        if ($path && preg_match('#/atk/([0-9]+)/detail#i', $path, $matches)) {
            $candidates[] = 'id:' . $matches[1];
        }

        if ($path) {
            $segments = array_values(array_filter(explode('/', trim($path, '/'))));
            if (!empty($segments)) {
                $candidates[] = trim(urldecode((string) end($segments)));
            }
        }

        if ($query) {
            $this->addQueryCandidates($query, $candidates);
        }
    }

    private function addQueryCandidates($query, array &$candidates): void
    {
        parse_str($query, $params);

        foreach (['code', 'token', 'qr', 'atk', 'kode', 'kode_atk', 'id'] as $key) {
            if (empty($params[$key])) {
                continue;
            }

            $value = trim((string) $params[$key]);
            $candidates[] = ctype_digit($value) ? 'id:' . $value : $value;
        }
    }

    private function addPatternCandidates(array &$candidates): void
    {
        foreach ($candidates as $candidate) {
            if (preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12})/i', $candidate, $uuidMatch)) {
                $candidates[] = strtolower($uuidMatch[1]);
            }

            if (preg_match('/^(?:ATK-QR:|QR:)\s*(.+)$/i', $candidate, $prefixed)) {
                $candidates[] = trim($prefixed[1]);
            }

            if (preg_match('/^[A-Za-z0-9]+-[A-Za-z]+-\d+$/', $candidate) || preg_match('/^[A-Za-z]+-\d+$/', $candidate)) {
                $candidates[] = str_replace('-', '/', $candidate);
            }
        }
    }

    private function uniqueCandidates(array $candidates): array
    {
        $normalized = [];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);

            if ($candidate === '') {
                continue;
            }

            $normalized[$candidate] = true;
        }

        return array_keys($normalized);
    }
}
