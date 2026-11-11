<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCode
{
    /**
     * Render teks menjadi SVG QR code yang aman ditanam di Blade.
     */
    public static function svg(string $text, int $size = 220): string
    {
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle($size, 1),
                new SvgImageBackEnd,
            ),
        );

        return $writer->writeString($text);
    }
}
