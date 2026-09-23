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
    public function store(UploadedFile $file, string $folder, int $maxWidth = 1600, ?int $thumbWidth = 640, int $quality = 80, bool $private = false, bool $square = false): array
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
        }
    }
}
