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

        $atk->forceFill([
            'qr_code_value' => $qrValue,
            'qr_code_image' => $qrPath,
            'qr_token' => $atk->qr_token,
        ])->save();

        return $atk->fresh();
    }

    public function valueFor(Atk $atk)
    {
        return url('/atk/scan/lookup?code=' . urlencode($atk->qr_token));
    }
}
