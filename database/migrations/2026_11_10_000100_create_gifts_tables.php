<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leader gift ladder. Each gift unlocks when a leader's lifetime points reach
 * points_required. Points are never spent, so every gift up to that total is
 * open. A gift with several options lets the leader pick one of them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('description_ar', 500)->nullable();
            $table->unsignedInteger('points_required')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('gift_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_id')->constrained()->cascadeOnDelete();
            $table->string('image');
            $table->string('label', 120)->nullable();
            $table->string('label_ar', 120)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('gift_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leader_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gift_option_id')->nullable()->constrained()->nullOnDelete();
            // requested | delivered
            $table->string('status', 20)->default('requested')->index();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['leader_id', 'gift_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_claims');
        Schema::dropIfExists('gift_options');
        Schema::dropIfExists('gifts');
    }
};
