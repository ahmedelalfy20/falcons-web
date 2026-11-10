<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Converts uploads to resized WebP (plus an optional thumbnail) using GD so
 * the site never ships multi-megabyte originals.
 *
 * Public images return a web path ("storage/…"); private images (e.g. payment
 * screenshots) are stored on the local disk and returned as a disk path that
 * is only ever served through an authorised controller.
 */
class ImageOptimizer
{
    /** @return array{path:string, thumb:?string, width:int, height:int} */
    public function store(UploadedFile $file, string $folder, int $maxWidth = 1600, ?int $thumbWidth = 640, int $quality = 80, bool $private = false, bool $square = false, bool $cutout = false): array
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $source) {
            throw new RuntimeException('Unsupported image.');
        }
        imagepalettetotruecolor($source);
        imagealphablending($source, true);
        imagesavealpha($source, true);
        $source = $this->applyExifOrientation($source, $file);

        if ($square) {
            $source = $this->cropSquare($source);
        }
        if ($cutout) {
            $source = $this->removePlainBackground($this->scaleDown($source, $maxWidth));
        }

        $disk = $private ? 'local' : 'public';
        $name = Str::random(24);
        $main = $this->resizeAndSave($source, "{$folder}/{$name}.webp", $maxWidth, $quality, $disk);
        $thumb = $thumbWidth ? $this->resizeAndSave($source, "{$folder}/{$name}-thumb.webp", $thumbWidth, 74, $disk) : null;
        imagedestroy($source);

        $prefix = $private ? '' : 'storage/';

        return [
            'path' => $prefix.$main['path'],
            'thumb' => $thumb ? $prefix.$thumb['path'] : null,
            'width' => $main['w'],
            'height' => $main['h'],
        ];
    }

    private function resizeAndSave(\GdImage $src, string $path, int $maxWidth, int $quality, string $disk): array
    {
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w > $maxWidth) {
            $nh = (int) round($h * $maxWidth / $w);
            $dst = imagecreatetruecolor($maxWidth, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxWidth, $nh, $w, $h);
            [$w, $h] = [$maxWidth, $nh];
        } else {
            $dst = $src;
        }
        ob_start();
        imagewebp($dst, null, $quality);
        $bytes = ob_get_clean();
        if ($dst !== $src) {
            imagedestroy($dst);
        }
        Storage::disk($disk)->put($path, $bytes);

        return ['path' => $path, 'w' => $w, 'h' => $h];
    }

    private function scaleDown(\GdImage $src, int $maxWidth): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w <= $maxWidth) {
            return $src;
        }
        $nh = (int) round($h * $maxWidth / $w);
        $dst = imagecreatetruecolor($maxWidth, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxWidth, $nh, $w, $h);
        imagedestroy($src);

        return $dst;
    }

    /**
     * Product shots on a plain white/light studio background become cut-outs:
     * the background connected to the image edges turns transparent, with a soft
     * edge so there is no white halo. Photos whose corners are not a plain light
     * colour (lifestyle shots, already-transparent PNGs) are left untouched.
     */
    private function removePlainBackground(\GdImage $img): \GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w < 16 || $h < 16) {
            return $img;
        }
        $light = function (int $c): bool {
            $a = ($c >> 24) & 0x7F;
            if ($a > 100) {
                return false; // already transparent — nothing to remove
            }
            $r = ($c >> 16) & 0xFF; $g = ($c >> 8) & 0xFF; $b = $c & 0xFF;

            return min($r, $g, $b) >= 238 && max($r, $g, $b) - min($r, $g, $b) <= 14;
        };
        foreach ([[0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1], [(int) ($w / 2), 0], [0, (int) ($h / 2)]] as [$x, $y]) {
            if (! $light(imagecolorat($img, $x, $y))) {
                return $img;
            }
        }

        imagealphablending($img, false);
        imagesavealpha($img, true);
        $seen = new \SplFixedArray($w * $h);
        $queue = new \SplQueue;
        for ($x = 0; $x < $w; $x++) { $queue->enqueue([$x, 0]); $queue->enqueue([$x, $h - 1]); }
        for ($y = 0; $y < $h; $y++) { $queue->enqueue([0, $y]); $queue->enqueue([$w - 1, $y]); }
        $edge = [];
        while (! $queue->isEmpty()) {
            [$x, $y] = $queue->dequeue();
            $i = $y * $w + $x;
            if ($seen[$i]) {
                continue;
            }
            $seen[$i] = true;
            $c = imagecolorat($img, $x, $y);
            if (! $light($c)) {
                $edge[] = [$x, $y, $c]; // first non-background pixel: soften later

                continue;
            }
            imagesetpixel($img, $x, $y, 0x7F000000 | ($c & 0xFFFFFF));
            if ($x > 0) $queue->enqueue([$x - 1, $y]);
            if ($x < $w - 1) $queue->enqueue([$x + 1, $y]);
            if ($y > 0) $queue->enqueue([$x, $y - 1]);
            if ($y < $h - 1) $queue->enqueue([$x, $y + 1]);
        }
        // Feather the outline: near-white edge pixels get partial transparency.
        foreach ($edge as [$x, $y, $c]) {
            $r = ($c >> 16) & 0xFF; $g = ($c >> 8) & 0xFF; $b = $c & 0xFF;
            $lum = ($r + $g + $b) / 3;
            if ($lum > 200) {
                $alpha = (int) min(110, max(0, ($lum - 200) / 32 * 110));
                imagesetpixel($img, $x, $y, ($alpha << 24) | ($c & 0xFFFFFF));
            }
        }

        // Trim the now-empty margins so the product fills its frame.
        [$minX, $minY, $maxX, $maxY] = [$w, $h, -1, -1];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if ($seen[$y * $w + $x] && ((imagecolorat($img, $x, $y) >> 24) & 0x7F) >= 120) {
                    continue; // removed background
                }
                if ($x < $minX) $minX = $x;
                if ($x > $maxX) $maxX = $x;
                if ($y < $minY) $minY = $y;
                if ($y > $maxY) $maxY = $y;
            }
        }
        if ($maxX > $minX + 8 && $maxY > $minY + 8) {
            $pad = (int) round(max($maxX - $minX, $maxY - $minY) * 0.04);
            $minX = max(0, $minX - $pad); $minY = max(0, $minY - $pad);
            $cw = min($w, $maxX + $pad + 1) - $minX; $ch = min($h, $maxY + $pad + 1) - $minY;
            $crop = imagecreatetruecolor($cw, $ch);
            imagealphablending($crop, false);
            imagesavealpha($crop, true);
            imagecopy($crop, $img, 0, 0, $minX, $minY, $cw, $ch);
            imagedestroy($img);
            $img = $crop;
        }

        return $img;
    }

    /** Centre-crop to a square, biased towards the top (faces in portraits). */
    private function cropSquare(\GdImage $src): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $size = min($w, $h);
        $x = (int) (($w - $size) / 2);
        $y = $h > $w ? (int) (($h - $size) * 0.2) : 0;
        $dst = imagecreatetruecolor($size, $size);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopy($dst, $src, 0, 0, $x, $y, $size, $size);
        imagedestroy($src);

        return $dst;
    }

    /** Phone photos often carry an EXIF rotation instead of rotated pixels. */
    private function applyExifOrientation(\GdImage $img, UploadedFile $file): \GdImage
    {
        if (! function_exists('exif_read_data') || ! in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg'], true)) {
            return $img;
        }
        $orientation = (int) (@exif_read_data($file->getRealPath())['Orientation'] ?? 1);
        $angle = match ($orientation) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
        if ($angle === 0) {
            return $img;
        }
        $rotated = imagerotate($img, $angle, 0);
        imagedestroy($img);

        return $rotated;
    }

    public function delete(?string $path, bool $private = false): void
    {
        if (! $path) {
            return;
        }
        if ($private) {
            Storage::disk('local')->delete($path);
            Storage::disk('local')->delete(str_replace('.webp', '-thumb.webp', $path));
        } elseif (str_starts_with($path, 'storage/')) {
            Storage::disk('public')->delete(substr($path, 8));
            Storage::disk('public')->delete(str_replace('.webp', '-thumb.webp', substr($path, 8)));
        }
    }
}
