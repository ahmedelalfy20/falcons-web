<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\RoundStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\AuditLog;
use App\Models\LeaderScore;
use App\Models\Registration;
use App\Services\RegistrationService;
use App\Services\RoundService;
use App\Services\ScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompetitionServicesTest extends TestCase
{
    use RefreshDatabase;

    private function regs(): RegistrationService
    {
        return app(RegistrationService::class);
    }

    public function test_referral_codes_are_random_formatted_and_unique(): void
    {
        $c = $this->competition();
        $a = $this->leader($c, '01000000001');
        $b = $this->leader($c, '01000000002');
        $this->assertMatchesRegularExpression('/^LDR-[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{8}$/', $a->unique_code);
        $this->assertNotSame($a->unique_code, $b->unique_code);
        $this->assertNotSame((string) $a->id, substr($a->unique_code, 4));
    }

    public function test_new_registration_is_pending_and_adds_no_points(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $leader = $this->leader($c);

        $r = $this->regs()->submit($leader->unique_code, $this->participant(1));

        $this->assertSame(RegistrationStatus::Pending, $r->status);
        $row = app(ScoreService::class)->rankOf($round, $leader->id);
        $this->assertSame(0, (int) $row->accepted);
        $this->assertSame(1, (int) $row->pending);
    }

    public function test_accept_is_atomic_recalculates_score_and_writes_audit(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $leader = $this->leader($c);
        $admin = $this->reviewer();
        $r = $this->regs()->submit($leader->unique_code, $this->participant(1));

        $this->regs()->accept($r, $admin);

        $this->assertSame(RegistrationStatus::Accepted, $r->fresh()->status);
        $this->assertSame($admin->id, $r->fresh()->reviewed_by);
        $this->assertSame(1, LeaderScore::where('round_id', $round->id)->where('leader_id', $leader->id)->value('accepted_count'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'registration.accepted', 'entity_id' => $r->id, 'user_id' => $admin->id]);
    }

    public function test_accept_rolls_back_when_audit_fails(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $leader = $this->leader($c);
        $r = $this->regs()->submit($leader->unique_code, $this->participant(1));

        // Simulate a failure in the last step of the transaction.
        AuditLog::creating(function () {
            throw new \RuntimeException('audit store down');
        });

        try {
            $this->regs()->accept($r, $this->reviewer());
            $this->fail('Expected exception');
        } catch (\RuntimeException) {
        }
        AuditLog::flushEventListeners();

        $this->assertSame(RegistrationStatus::Pending, $r->fresh()->status);
        $this->assertSame(0, LeaderScore::where('round_id', $round->id)->where('leader_id', $leader->id)->value('accepted_count'));
    }

    public function test_reject_keeps_score_and_decisions_are_final(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $leader = $this->leader($c);
        $admin = $this->reviewer();
        $r = $this->regs()->submit($leader->unique_code, $this->participant(1));

        $this->regs()->reject($r, $admin);
        $this->assertSame(0, (int) app(ScoreService::class)->rankOf($round, $leader->id)->accepted);
        $this->assertDatabaseHas('audit_logs', ['action' => 'registration.rejected', 'entity_id' => $r->id]);

        $this->expectException(BusinessRuleException::class);
        $this->regs()->accept($r->fresh(), $admin);
    }

    public function test_duplicate_phone_is_blocked_even_with_different_formatting(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $a = $this->leader($c, '01000000001');
        $b = $this->leader($c, '01000000002');
        $this->regs()->submit($a->unique_code, $this->participant(1, ['phone' => '01112345678']));

        try {
            $this->regs()->submit($b->unique_code, $this->participant(2, ['phone' => '+20 111 234 5678']));
            $this->fail('Duplicate accepted');
        } catch (BusinessRuleException $e) {
            $this->assertSame('duplicate', $e->reason);
        }
        $this->assertSame(1, Registration::count());
    }

    public function test_duplicate_email_is_blocked(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $a = $this->leader($c);
        $this->regs()->submit($a->unique_code, $this->participant(1, ['email' => 'Same@Example.com']));
        $this->expectException(BusinessRuleException::class);
        $this->regs()->submit($a->unique_code, $this->participant(2, ['email' => 'same@example.com']));
    }

    public function test_database_unique_constraint_backs_up_duplicate_check(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $leader = $this->leader($c);
        $r = $this->regs()->submit($leader->unique_code, $this->participant(1));

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('registrations')->insert(array_merge(
            collect($r->getAttributes())->except(['id', 'public_token'])->all(),
            ['public_token' => str_repeat('x', 40)]
        ));
    }

    public function test_invalid_inactive_and_closed_states_are_rejected(): void
    {
        $c = $this->competition();
        $rounds = app(RoundService::class);
        $round = $this->runningRound($c);
        $leader = $this->leader($c);

        $this->assertReason('invalid_code', fn () => $this->regs()->submit('LDR-ZZZZZZZZ', $this->participant(1)));

        $leader->update(['status' => 'suspended']);
        $this->assertReason('leader_inactive', fn () => $this->regs()->submit($leader->unique_code, $this->participant(1)));
        $leader->update(['status' => 'active']);

        $rounds->setRegistration($round, false);
        $this->assertReason('registration_closed', fn () => $this->regs()->submit($leader->unique_code, $this->participant(1)));
        $rounds->setRegistration($round->fresh(), true);

        $rounds->pause($round->fresh());
        $this->assertReason('registration_closed', fn () => $this->regs()->submit($leader->unique_code, $this->participant(1)));
        $rounds->resume($round->fresh());

        $rounds->finish($round->fresh());
        $this->assertReason('competition_finished', fn () => $this->regs()->submit($leader->unique_code, $this->participant(1)));

        $c->update(['status' => 'archived']);
        $this->assertReason('competition_inactive', fn () => $this->regs()->submit($leader->unique_code, $this->participant(1)));
    }

    public function test_leader_from_another_competition_cannot_be_used(): void
    {
        $old = $this->competition();
        $oldLeader = $this->leader($old);
        $old->update(['status' => 'archived']);
        $current = $this->competition();
        $this->runningRound($current);

        $this->assertReason('competition_inactive', fn () => $this->regs()->submit($oldLeader->unique_code, $this->participant(1)));
    }

    public function test_timer_pause_resume_add_remove_is_server_authoritative(): void
    {
        $this->travelTo(now()->startOfMinute());
        $c = $this->competition();
        $rounds = app(RoundService::class);
        $round = $this->runningRound($c, 5 * 3600 + 32 * 60 + 14); // 05:32:14

        $this->assertSame(19934, $round->remainingSeconds());
        $round = $rounds->adjustTime($round, 30 * 60);
        $this->assertSame(21734, $round->remainingSeconds()); // 06:02:14

        $this->travel(100)->seconds();
        $round = $rounds->pause($round);
        $this->assertSame(RoundStatus::Paused, $round->status);
        $this->assertSame(21634, $round->remainingSeconds());

        $this->travel(2)->hours(); // paused time does not count
        $this->assertSame(21634, $round->fresh()->remainingSeconds());

        $round = $rounds->resume($round);
        $this->assertSame(21634, $round->remainingSeconds());

        $round = $rounds->adjustTime($round, -634);
        $this->assertSame(21000, $round->remainingSeconds());
        $this->assertDatabaseHas('audit_logs', ['action' => 'round.time_added']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'round.time_removed']);
    }

    public function test_round_finishes_automatically_when_timer_expires(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c, 120);
        $leader = $this->leader($c);
        $pending = $this->regs()->submit($leader->unique_code, $this->participant(1));

        $this->travel(3)->minutes();
        $this->artisan('competition:tick')->assertSuccessful();

        $round->refresh();
        $this->assertSame(RoundStatus::Finished, $round->status);
        $this->assertFalse($round->registration_enabled);
        $this->assertDatabaseHas('audit_logs', ['action' => 'round.auto_finished', 'entity_id' => $round->id]);
        $this->assertReason('competition_finished', fn () => $this->regs()->submit($leader->unique_code, $this->participant(2)));

        // Pending registrations stay reviewable after the round ends.
        $this->regs()->accept($pending, $this->reviewer());
        $this->assertSame(1, (int) app(ScoreService::class)->rankOf($round, $leader->id)->accepted);
    }

    public function test_expired_round_is_finished_lazily_even_without_scheduler(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c, 60);
        $leader = $this->leader($c);
        $this->travel(2)->minutes();

        $this->assertReason('competition_finished', fn () => $this->regs()->submit($leader->unique_code, $this->participant(1)));
        $this->assertSame(RoundStatus::Finished, $round->fresh()->status);
    }

    public function test_removing_all_time_finishes_the_round(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c, 600);
        $round = app(RoundService::class)->adjustTime($round, -900);
        $this->assertSame(RoundStatus::Finished, $round->status);
    }

    public function test_invalid_state_transitions_are_prevented(): void
    {
        $c = $this->competition();
        $rounds = app(RoundService::class);
        $round = $rounds->create($c, ['duration_seconds' => 600]);

        $this->assertReason('invalid_transition', fn () => $rounds->pause($round));
        $this->assertReason('invalid_transition', fn () => $rounds->resume($round));
        $round = $rounds->start($round);
        $this->assertReason('invalid_transition', fn () => $rounds->start($round));
        $rounds->finish($round);
        $this->assertReason('invalid_transition', fn () => $rounds->start($round->fresh()));
        $this->assertReason('invalid_transition', fn () => $rounds->resume($round->fresh()));
    }

    public function test_new_round_keeps_history_and_resets_scores(): void
    {
        $c = $this->competition(['duplicate_scope' => 'round']);
        $round1 = $this->runningRound($c);
        $a = $this->leader($c, '01000000001', 'Leader A');
        $b = $this->leader($c, '01000000002', 'Leader B');
        $admin = $this->reviewer();
        foreach (range(1, 3) as $i) {
            $this->regs()->accept($this->regs()->submit($a->unique_code, $this->participant($i)), $admin);
        }
        $this->regs()->accept($this->regs()->submit($b->unique_code, $this->participant(10)), $admin);

        $round2 = app(RoundService::class)->startNewRound($c, 3600, true);

        $this->assertSame(2, $round2->number);
        $this->assertSame(RoundStatus::Finished, $round1->fresh()->status);
        $board1 = app(ScoreService::class)->leaderboard($round1->fresh());
        $board2 = app(ScoreService::class)->leaderboard($round2);
        $this->assertSame([3, 1], $board1->pluck('score')->all());
        $this->assertSame([0, 0], $board2->pluck('score')->all());

        // With round-scoped duplicates the same participant may join the new round.
        $this->regs()->submit($a->unique_code, $this->participant(1));
        $this->assertSame(5, Registration::count());
    }

    public function test_competition_scoped_duplicates_block_across_rounds(): void
    {
        $c = $this->competition(['duplicate_scope' => 'competition']);
        $this->runningRound($c);
        $a = $this->leader($c);
        $this->regs()->submit($a->unique_code, $this->participant(1));
        app(RoundService::class)->startNewRound($c, 3600, true);

        $this->assertReason('duplicate', fn () => $this->regs()->submit($a->unique_code, $this->participant(1)));
    }

    public function test_leaderboard_tie_breaker_is_deterministic(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $a = $this->leader($c, '01000000001', 'A');
        $b = $this->leader($c, '01000000002', 'B');
        $z = $this->leader($c, '01000000003', 'Z');
        $admin = $this->reviewer();

        // B reaches 2 first; A reaches 2 later -> B ranks above A. Z has 0.
        $b1 = $this->regs()->submit($b->unique_code, $this->participant(1));
        $this->travel(1)->minutes();
        $a1 = $this->regs()->submit($a->unique_code, $this->participant(2));
        $this->travel(1)->minutes();
        $b2 = $this->regs()->submit($b->unique_code, $this->participant(3));
        $this->travel(1)->minutes();
        $a2 = $this->regs()->submit($a->unique_code, $this->participant(4));
        // Review order must NOT affect the ranking.
        foreach ([$a2, $a1, $b2, $b1] as $r) {
            $this->regs()->accept($r, $admin);
        }

        $board = app(ScoreService::class)->leaderboard($round);
        $this->assertSame(['B', 'A', 'Z'], $board->pluck('name')->all());
        $this->assertSame([1, 2, 3], $board->pluck('rank')->all());
    }

    public function test_recalculation_repairs_a_corrupted_cache(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $leader = $this->leader($c);
        $this->regs()->accept($this->regs()->submit($leader->unique_code, $this->participant(1)), $this->reviewer());
        LeaderScore::query()->update(['accepted_count' => 99]);

        $this->artisan('competition:recalculate-scores', ['round' => $round->id])->assertSuccessful();
        $this->assertSame(1, LeaderScore::where('leader_id', $leader->id)->value('accepted_count'));
    }

    public function test_audit_logs_are_immutable(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $log = AuditLog::first();
        $this->expectException(\LogicException::class);
        $log->update(['action' => 'tampered']);
    }

    private function assertReason(string $reason, \Closure $fn): void
    {
        try {
            $fn();
            $this->fail("Expected BusinessRuleException [{$reason}]");
        } catch (BusinessRuleException $e) {
            $this->assertSame($reason, $e->reason, $e->getMessage());
        }
    }
}
