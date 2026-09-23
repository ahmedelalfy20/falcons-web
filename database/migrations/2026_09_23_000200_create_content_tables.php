<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('scanners', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('tagline')->nullable();
            $table->string('tagline_ar')->nullable();
            $table->string('summary', 500)->nullable();
            $table->string('summary_ar', 500)->nullable();
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('timeframe', 60)->nullable();
            $table->string('methodology', 60)->nullable();
            $table->unsignedTinyInteger('targets')->nullable();
            // [{title, title_ar, text, text_ar}]
            $table->json('features')->nullable();
            // ['images/scanners/wolf/1.webp', ...]
            $table->json('images')->nullable();
            $table->string('accent', 9)->default('#D6BD8E');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('role')->nullable();
            $table->string('role_ar')->nullable();
            $table->string('city')->nullable();
            $table->string('city_ar')->nullable();
            $table->text('bio')->nullable();
            $table->text('bio_ar')->nullable();
            $table->string('image')->nullable();
            $table->json('links')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            // trading | marketing
            $table->string('track', 20)->index();
            $table->unsignedTinyInteger('level');
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->string('subtitle', 400)->nullable();
            $table->string('subtitle_ar', 400)->nullable();
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->json('highlights')->nullable();
            $table->json('highlights_ar')->nullable();
            $table->string('difficulty', 30)->nullable(); // beginner|intermediate|advanced|professional
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['track', 'level']);
        });

        Schema::create('gallery_images', function (Blueprint $table) {
            $table->id();
            $table->string('collection', 30)->default('academy')->index(); // academy | gifts
            $table->string('path');
            $table->string('thumb_path')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('caption')->nullable();
            $table->string('caption_ar')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('free_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('description_ar', 500)->nullable();
            $table->string('duration', 10)->nullable();
            $table->string('difficulty', 30)->nullable();
            $table->string('video_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['free_videos', 'gallery_images', 'courses', 'team_members', 'scanners', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
