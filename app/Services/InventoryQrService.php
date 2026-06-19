<?php

namespace App\Services;

use App\Models\Inventory;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InventoryQrService
{
    private const DIRECTORY = 'inventory/qrcodes';

    public function ensure(Inventory $inventory, $force = false)
    {
        if (!$inventory->exists) {
            return $inventory;
        }

        if (!$inventory->qr_token) {
            $inventory->qr_token = (string) Str::uuid();
        }

        $qrValue = $this->valueFor($inventory);
        $qrPath = self::DIRECTORY . '/' . $inventory->id . '.png';
        $needsImage = $force
            || $inventory->qr_code_value !== $qrValue
            || $inventory->qr_code_image !== $qrPath
            || !Storage::disk('public')->exists($qrPath);

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

            Storage::disk('public')->put($qrPath, $result->getString());
        }

        $inventory->forceFill([
            'qr_code_value' => $qrValue,
            'qr_code_image' => $qrPath,
            'qr_token' => $inventory->qr_token,
        ])->save();

        return $inventory->fresh();
    }

    public function valueFor(Inventory $inventory)
    {
        return url('/inventory/scan/lookup?code=' . urlencode($inventory->qr_token));
    }

    public function findByInput($value)
    {
        $candidates = $this->extractCandidates($value);
        if (empty($candidates)) {
            return null;
        }

        foreach ($candidates as $candidate) {
            $inventory = $this->findByCandidate($candidate);

            if ($inventory) {
                return $inventory;
            }
        }

        return null;
    }

    private function findByCandidate($candidate)
    {
        if (preg_match('/^id:([0-9]+)$/', $candidate, $matches)) {
            return Inventory::find((int) $matches[1]);
        }

        return Inventory::where('qr_token', $candidate)
            ->orWhere('qr_code_value', $candidate)
            ->orWhere('kode_barang', $candidate)
            ->first();
    }

    private function extractCandidates($value)
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

        if ($path && preg_match('#/inventory/([0-9]+)/detail#i', $path, $matches)) {
            $candidates[] = 'id:' . $matches[1];
        }

        if ($path && preg_match('~/assets/detail/([^/?#]+)~i', $path, $matches)) {
            $candidates[] = trim(urldecode($matches[1]));
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

        foreach (['code', 'token', 'qr', 'asset', 'kode', 'kode_barang', 'id'] as $key) {
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

            if (preg_match('/^(?:INV-ASSET:|ASSET-|INV-QR:|QR:)\s*(.+)$/i', $candidate, $prefixed)) {
                $candidates[] = trim($prefixed[1]);
            }

            if (preg_match('/^[A-Za-z]+-\d+$/', $candidate)) {
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
