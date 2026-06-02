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
}
