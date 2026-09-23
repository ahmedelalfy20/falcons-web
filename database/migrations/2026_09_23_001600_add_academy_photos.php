<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** 18 more "Inside the Academy" photos (events, graduation and the desert camp). */
return new class extends Migration
{
    public const PHOTOS = [
            [6, 1280, 854],
            [7, 1280, 854],
            [8, 1600, 1066],
            [9, 1280, 854],
            [10, 1280, 854],
            [11, 1600, 1066],
            [12, 1280, 854],
            [13, 1600, 1066],
            [14, 1600, 1066],
            [15, 1280, 854],
            [16, 1280, 854],
            [17, 1600, 1066],
            [18, 1280, 854],
            [19, 1600, 1066],
            [20, 1280, 854],
            [21, 1600, 1066],
            [22, 1280, 854],
            [23, 1600, 1066],
    ];

    public function up(): void
    {
        $order = (int) DB::table('gallery_images')->where('collection', 'academy')->max('sort_order');
        foreach (self::PHOTOS as [$n, $w, $h]) {
            $path = "media/gallery/{$n}.webp";
            if (DB::table('gallery_images')->where('path', $path)->exists()) {
                continue;
            }
            DB::table('gallery_images')->insert([
                'collection' => 'academy', 'path' => $path, 'thumb_path' => "media/gallery/{$n}-thumb.webp",
                'width' => $w, 'height' => $h, 'sort_order' => ++$order, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('gallery_images')->whereIn('path', array_map(fn ($p) => "media/gallery/{$p[0]}.webp", self::PHOTOS))->delete();
    }
};
