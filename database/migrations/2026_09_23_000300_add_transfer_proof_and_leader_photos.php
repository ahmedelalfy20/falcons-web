<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - registrations.transfer_path: private path of the participant's payment-transfer screenshot
 * - leaders.photo: public profile photo shown on the live leaderboard
 * - leaders.approved_at / approved_by: leader approval workflow (status: pending → active | rejected)
 * Existing competitions switch to: email required, transfer proof required, leaders need approval.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('transfer_path')->nullable()->after('notes');
        });

        Schema::table('leaders', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('email');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
        });

        // Leaders that were already active count as approved.
        DB::table('leaders')->where('status', 'active')->whereNull('approved_at')->update(['approved_at' => now()]);

        foreach (DB::table('competitions')->get(['id', 'configuration']) as $c) {
            $config = json_decode($c->configuration ?? '{}', true) ?: [];
            $config['require_email'] = true;
            $config['require_transfer_proof'] = true;
            $config['leader_auto_approve'] = false;
            DB::table('competitions')->where('id', $c->id)->update(['configuration' => json_encode($config)]);
        }
    }

    public function down(): void
    {
        Schema::table('leaders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['photo', 'approved_at']);
        });
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('transfer_path');
        });
    }
};
