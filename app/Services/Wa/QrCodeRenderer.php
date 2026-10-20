<?php

namespace App\Services\Wa;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeRenderer
{
    public function dataUri(string $data, int $size = 320): string
    {
        $qrCode = new QrCode(
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 8,
        );

        return (new SvgWriter)->write($qrCode)->getDataUri();
    }
}
