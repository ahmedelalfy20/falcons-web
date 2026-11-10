<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The leader gift ladder (photos ship with the site in public/media/gifts).
 * Gifts that already exist by English name are left untouched, so running this
 * on a site where the gifts were added from the admin changes nothing.
 */
return new class extends Migration
{
    private function gifts(): array
    {
        return [
            ['AirPods Pro', 'سماعة AirPods Pro', 60,
                'Apple AirPods Pro with active noise cancellation.', 'سماعة Apple AirPods Pro اللاسلكية بخاصية عزل الضوضاء.',
                [['airpods-pro', 'contain', null, null]]],
            ['Smart Watch', 'ساعة سمارت', 180,
                'A smart watch for your workouts, health and notifications.', 'ساعة ذكية لمتابعة صحتك وتمارينك وإشعاراتك.',
                [['smart-watch', 'contain', null, null]]],
            ['Trip to Dahab', 'رحلة لدهب', 400,
                'A getaway to Dahab on the Red Sea.', 'رحلة لدهب على البحر الأحمر.',
                [['dahab-trip', 'cover', null, null]]],
            ['iPhone 13 or iPad A16', 'iPhone 13 أو iPad A16', 1400,
                'Choose the standard iPhone 13 or Apple iPad A16.', 'اختار بين iPhone 13 العادي أو iPad A16 من Apple.',
                [['iphone-13', 'contain', 'iPhone 13', 'iPhone 13'], ['ipad-a16', 'contain', 'iPad A16', 'iPad A16']]],
            ['MacBook Air M1 or iPhone 14', 'MacBook Air M1 أو iPhone 14', 2700,
                'Choose a MacBook Air M1 or the standard iPhone 14.', 'اختار بين MacBook Air M1 أو iPhone 14 العادي.',
                [['macbook-air-m1', 'contain', 'MacBook Air M1', 'MacBook Air M1'], ['iphone-14', 'contain', 'iPhone 14', 'iPhone 14']]],
            ['iPhone 18 Pro Max', 'iPhone 18 Pro Max', 5200,
                'The new iPhone 18 Pro Max.', 'أحدث iPhone 18 Pro Max.',
                [['iphone-18-pro-max', 'contain', null, null]]],
            ['Chery Arrizo 5 (2026)', 'عربية شيري أريزو 5 موديل 2026', 20200,
                'The grand prize: a brand-new Chery Arrizo 5, 2026 model.', 'الجايزة الكبرى: عربية شيري أريزو 5 زيرو موديل 2026.',
                [['chery-arrizo-5', 'cover', null, null]]],
        ];
    }

    public function up(): void
    {
        // Tests build their own gifts; this is site content only.
        if (app()->environment('testing') && ! app()->bound('seed-gift-ladder')) {
            return;
        }
        $now = now();
        foreach ($this->gifts() as [$name, $nameAr, $points, $desc, $descAr, $options]) {
            if (DB::table('gifts')->where('name', $name)->exists()) {
                continue;
            }
            $giftId = DB::table('gifts')->insertGetId([
                'name' => $name, 'name_ar' => $nameAr, 'points_required' => $points,
                'description' => $desc, 'description_ar' => $descAr, 'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ($options as $i => [$file, $fit, $label, $labelAr]) {
                DB::table('gift_options')->insert([
                    'gift_id' => $giftId, 'image' => "media/gifts/{$file}.webp", 'fit' => $fit,
                    'label' => $label, 'label_ar' => $labelAr, 'sort_order' => $i,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Content migration: gifts are managed from the admin afterwards.
    }
};
