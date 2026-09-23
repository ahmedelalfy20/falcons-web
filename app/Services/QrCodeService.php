<?php

namespace App\Services;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    public function svg(string $content, int $size = 320): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 2, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(12, 14, 13))),
            new SvgImageBackEnd(),
        );

        return (new Writer($renderer))->writeString($content);
    }

    public function png(string $content, int $size = 1024): string
    {
        $renderer = new GDLibRenderer($size, 4, 'png', 9, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(12, 14, 13)));

        return (new Writer($renderer))->writeString($content);
    }
}
