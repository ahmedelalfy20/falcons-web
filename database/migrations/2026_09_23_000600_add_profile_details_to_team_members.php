<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Leader profiles: events gallery, years of experience and the company they run.
 * Also fills in Rahma Mohamed's profile (Founder & CEO of 3AQRAB Team).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->json('gallery')->nullable()->after('image');
            $table->unsignedTinyInteger('experience_years')->nullable()->after('city_ar');
            $table->foreignId('company_id')->nullable()->after('experience_years')->constrained('companies')->nullOnDelete();
        });

        $gallery = array_map(fn ($n) => "media/team/rahma/event-{$n}.webp", [3, 2, 1, 4, 6, 7, 5]);
        DB::table('team_members')->where('slug', 'rahma-mohamed')->update([
            'name' => 'Rahma Mohamed',
            'name_ar' => 'رحمة محمد',
            'role' => 'Founder & CEO — 3AQRAB Team',
            'role_ar' => 'المؤسس والرئيس التنفيذي لفريق عقرب',
            'bio' => "Trading & Marketing specialist with 3 years of experience teaching and developing trading and marketing skills.\n\nShe works to build a generation that is more aware of digital opportunities and of work and communication skills, through hands-on training and continuous development.",
            'bio_ar' => "متخصصة في تعليم وتطوير مهارات التداول والـ Marketing بخبرة 3 سنوات.\n\nتسعى لبناء جيل أكثر وعيًا بالفرص الرقمية ومهارات العمل والتواصل من خلال التدريب العملي والتطوير المستمر.",
            'experience_years' => 3,
            'company_id' => DB::table('companies')->where('logo', 'media/companies/3aqrab.webp')->value('id'),
            'gallery' => json_encode($gallery),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['gallery', 'experience_years']);
        });
    }
};
