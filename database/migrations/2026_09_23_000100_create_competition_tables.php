<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar')->nullable();
            // draft | active | archived
            $table->string('status', 20)->default('draft')->index();
            // duplicate_scope, leader_auto_approve, public_leaderboard, default_round_minutes, require_email ...
            $table->json('configuration')->nullable();
            $table->timestamps();
        });

        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('name')->nullable();
            // draft | ready | running | paused | finished
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            // Seconds left on the clock while paused (source of truth while status = paused)
            $table->unsignedInteger('remaining_seconds')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->boolean('registration_enabled')->default(true);
            $table->timestamps();

            $table->unique(['competition_id', 'number']);
        });

        Schema::create('leaders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 32);
            $table->string('phone_normalized', 32);
            $table->string('email')->nullable();
            // Public referral code (e.g. LDR-X7K92M4P) — never a sequential id
            $table->string('unique_code', 16)->unique();
            // Opaque token used for QR landing links (rotatable without changing the code)
            $table->string('qr_token', 40)->unique();
            // active | suspended
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();

            $table->unique(['competition_id', 'phone_normalized']);
        });

        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leader_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone', 32);
            $table->string('phone_normalized', 32);
            $table->string('email')->nullable();
            $table->string('email_normalized')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('notes', 500)->nullable();
            // pending | accepted | rejected
            $table->string('status', 20)->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('review_note', 500)->nullable();
            // Unguessable token for the participant's result page
            $table->string('public_token', 40)->unique();
            $table->timestamps();

            // A participant can only appear once per round (hard DB guarantee).
            $table->unique(['round_id', 'phone_normalized']);
            $table->unique(['round_id', 'email_normalized']);
            $table->index(['round_id', 'status']);
            $table->index(['leader_id', 'round_id', 'status']);
            $table->index(['competition_id', 'phone_normalized']);
            $table->index(['competition_id', 'email_normalized']);
        });

        // Cached aggregate — accepted registrations remain the source of truth and
        // this table is fully rebuildable via ScoreService::recalculate().
        Schema::create('leader_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leader_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('accepted_count')->default(0);
            $table->unsignedInteger('pending_count')->default(0);
            $table->unsignedInteger('rejected_count')->default(0);
            // Submission time of the registration that brought the leader to the current score (tie-breaker)
            $table->timestamp('score_reached_at')->nullable();
            $table->timestamps();

            $table->unique(['round_id', 'leader_id']);
            $table->index(['round_id', 'accepted_count']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('action', 64)->index();
            $table->string('entity_type', 64)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('leader_scores');
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('leaders');
        Schema::dropIfExists('rounds');
        Schema::dropIfExists('competitions');
    }
};
