<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\RegistrationStatus;
use App\Enums\RoundStatus;
use App\Models\AuditLog;
use App\Models\Leader;
use App\Models\Registration;
use App\Models\Round;
use App\Models\Scanner;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\RegistrationService;
use App\Services\RoundService;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * End-to-end HTTP tests: public site, participant registration, leader area,
 * admin review, timer controls and every permission boundary.
 */
class HttpFlowTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- public site

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_public_pages_render_in_both_languages(): void
    {
        $this->seed(ContentSeeder::class);
        $c = $this->competition();
        $this->runningRound($c);

        foreach (['en', 'ar'] as $locale) {
            $this->withSession(['locale' => $locale]);
            foreach (['/', '/competition', '/register', '/competition/join', '/login', '/free-courses', '/scanners/wolf', '/leaders/rahma-mohamed'] as $url) {
                $this->get($url)->assertOk();
            }
        }
        $this->withSession(['locale' => 'ar'])->get('/')->assertSee('dir="rtl"', false)->assertSee('تعرّف على المؤسس');
    }

    public function test_homepage_reflects_requested_content_changes(): void
    {
        $this->seed(ContentSeeder::class);
        $html = $this->get('/')->assertOk()->getContent();

        // Founder is the opening hero, before scanners/leaders/courses.
        $this->assertLessThan(strpos($html, 'id="scanners"'), strpos($html, 'id="founder"'));
        // Amr Samir removed; rank progression removed.
        $this->assertStringNotContainsString('Amr Samir', $html);
        $this->assertStringNotContainsString('Rank Progression', $html);
        // Every leader card links to its own profile page.
        foreach (TeamMember::visible()->get() as $m) {
            $this->assertStringContainsString(route('team.show', $m), $html);
        }
        // Both course tracks, four levels each.
        $this->assertSame(4, \App\Models\Course::where('track', 'trading')->count());
        $this->assertSame(4, \App\Models\Course::where('track', 'marketing')->count());
    }

    public function test_new_scanner_appears_without_template_changes(): void
    {
        $this->seed(ContentSeeder::class);
        Scanner::create(['slug' => 'eagle', 'name' => 'Eagle Test', 'tagline' => 'New system', 'accent' => '#FF8800', 'sort_order' => 99, 'is_active' => true]);

        $this->get('/')->assertSee('Eagle Test')->assertSee(route('scanners.show', 'eagle'));
        $this->get('/scanners/eagle')->assertOk()->assertSee('Eagle Test');
    }

    public function test_leader_profile_without_bio_shows_placeholder_and_hidden_profiles_404(): void
    {
        $this->seed(ContentSeeder::class);
        TeamMember::where('slug', 'rahma-mohamed')->update(['bio' => null, 'bio_ar' => null]);
        $this->get('/leaders/rahma-mohamed')->assertOk()->assertSee('Biography coming soon');
        TeamMember::where('slug', 'rahma-mohamed')->update(['is_active' => false]);
        $this->get('/leaders/rahma-mohamed')->assertNotFound();
    }

    public function test_free_courses_invite_code_is_checked_on_the_server(): void
    {
        $this->seed(ContentSeeder::class);
        $video = \App\Models\FreeVideo::first();

        $this->get(route('free-courses.video', $video))->assertForbidden();
        $this->post('/free-courses/unlock', ['code' => 'wrong'])->assertSessionHasErrors('code');
        $this->post('/free-courses/unlock', ['code' => '1000000'])->assertRedirect('/free-courses');
        $this->get('/free-courses')->assertSee($video->title);
    }

    // ------------------------------------------------------ participant registration

    public function test_qr_link_shows_form_and_submission_is_pending(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $leader = $this->leader($c);

        $this->get('/register?ref='.$leader->unique_code)->assertOk()->assertSee($leader->name);
        // Lower-case / missing dash typed by hand still resolves.
        $this->get('/register?ref='.strtolower(str_replace('-', '', $leader->unique_code)))->assertOk()->assertSee($leader->name);

        $res = $this->post('/register', $this->registerForm($leader->unique_code));
        $reg = Registration::firstOrFail();
        $res->assertRedirect(route('registration.result', $reg->public_token));

        $this->assertSame(RegistrationStatus::Pending, $reg->status);
        $this->get(route('registration.result', $reg->public_token))
            ->assertSee('Registration submitted successfully.')
            ->assertSee('Your registration is currently pending review.')
            ->assertDontSee('Successfully counted');
        $this->assertSame(0, (int) app(\App\Services\ScoreService::class)->rankOf($round, $leader->id)->accepted);
    }

    public function test_result_page_reflects_decisions(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $leader = $this->leader($c);
        $reg = app(RegistrationService::class)->submit($leader->unique_code, $this->participant(1));

        app(RegistrationService::class)->accept($reg, $this->superAdmin());
        $this->getJson(route('live.registration', $reg->public_token))->assertJson(['status' => 'accepted']);
        $this->get(route('registration.result', $reg->public_token))->assertSee('Registration Approved.');
    }

    public function test_registration_errors_are_friendly(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $leader = $this->leader($c);

        $this->get('/register?ref=LDR-ZZZZZZZZ')->assertNotFound()->assertSee('Invalid referral code');

        // Duplicate phone (different formatting) is rejected with a field error.
        app(RegistrationService::class)->submit($leader->unique_code, $this->participant(1, ['phone' => '01110000001']));
        $this->from('/register?ref='.$leader->unique_code)
            ->post('/register', $this->registerForm($leader->unique_code, ['full_name' => 'Other Person', 'phone' => '+20 111 000 0001', 'email' => 'other@example.com']))
            ->assertSessionHasErrors('phone');
        $this->assertSame(1, Registration::count());

        // Invalid form data.
        $this->post('/register', ['ref' => $leader->unique_code, 'full_name' => 'x', 'phone' => '12', 'consent' => ''])
            ->assertSessionHasErrors(['full_name', 'phone', 'consent', 'city', 'team']);

        // Team must be one of the allowed teams.
        $this->post('/register', $this->registerForm($leader->unique_code, ['team' => 'Invalid Team']))
            ->assertSessionHasErrors('team');

        // City is mandatory.
        $this->post('/register', $this->registerForm($leader->unique_code, ['city' => '']))
            ->assertSessionHasErrors('city');

        // Registration closed by admin.
        app(RoundService::class)->setRegistration($round, false);
        $this->get('/register?ref='.$leader->unique_code)->assertOk()->assertSee('Registration is currently closed.');
        $this->post('/register', $this->registerForm($leader->unique_code, ['full_name' => 'Late Person', 'phone' => '01119998887', 'email' => 'late@example.com']))
            ->assertSessionHas('error_reason', 'registration_closed');

        // Inactive leader.
        app(RoundService::class)->setRegistration($round->fresh(), true);
        $leader->update(['status' => 'suspended']);
        $this->get('/register?ref='.$leader->unique_code)->assertSee('This leader is not currently accepting registrations.');
    }

    public function test_expired_timer_rejects_registrations_even_without_scheduler(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c, 60);
        $leader = $this->leader($c);

        $this->travel(2)->minutes();
        $this->post('/register', $this->registerForm($leader->unique_code, ['full_name' => 'Too Late', 'phone' => '01119998887']))
            ->assertSessionHas('error_reason', 'competition_finished');
        $this->assertSame(RoundStatus::Finished, $round->fresh()->status);
        $this->assertSame(0, Registration::count());
    }

    public function test_honeypot_blocks_bots(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $leader = $this->leader($c);
        $this->post('/register', ['ref' => $leader->unique_code, 'full_name' => 'Bot Name', 'phone' => '01112223334', 'consent' => '1', 'website' => 'spam'])
            ->assertSessionHasErrors('website');
        $this->assertSame(0, Registration::count());
    }

    public function test_registration_endpoint_is_rate_limited(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $leader = $this->leader($c);
        for ($i = 0; $i < 3; $i++) {
            $this->post('/register', ['ref' => $leader->unique_code, 'full_name' => 'Same Phone', 'phone' => '01112223334', 'consent' => '1']);
        }
        $this->post('/register', ['ref' => $leader->unique_code, 'full_name' => 'Same Phone', 'phone' => '01112223334', 'consent' => '1'])->assertStatus(429);
    }

    // --------------------------------------------------------------- leader area

    public function test_leader_signup_creates_code_qr_and_dashboard(): void
    {
        $c = $this->competition();
        $this->runningRound($c);

        $this->post('/competition/join', ['name' => 'Mona Adel', 'phone' => '01223334445', 'email' => 'mona@example.com', 'team' => 'Million Team', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'photo' => $this->image('me.jpg', 400, 400)])
            ->assertRedirect(route('leader.dashboard'));

        $leader = Leader::where('email', 'mona@example.com')->firstOrFail();
        $this->assertSame('Million Team', $leader->team);
        $this->assertMatchesRegularExpression('/^LDR-[A-Z0-9]{8}$/', $leader->unique_code);
        $this->get('/leader')->assertOk()->assertSee($leader->unique_code)->assertSee(route('register', ['ref' => $leader->unique_code]), false);
        $this->get('/leader/qr.svg')->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
        $this->get('/leader/qr.png')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->getJson('/leader/live')->assertOk()->assertJsonPath('stats.accepted', 0);
    }

    public function test_registration_requires_email_and_transfer_screenshot_stored_privately(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $leader = $this->leader($c);

        $this->post('/register', $this->registerForm($leader->unique_code, ['email' => '', 'transfer_screenshot' => null]))
            ->assertSessionHasErrors(['email', 'transfer_screenshot']);
        $this->post('/register', $this->registerForm($leader->unique_code, ['transfer_screenshot' => \Illuminate\Http\UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf')]))
            ->assertSessionHasErrors('transfer_screenshot');
        $this->assertSame(0, Registration::count());

        $this->post('/register', $this->registerForm($leader->unique_code))->assertRedirect();
        $reg = Registration::firstOrFail();
        $this->assertNotNull($reg->transfer_path);
        Storage::disk('local')->assertExists($reg->transfer_path);
        Storage::disk('public')->assertMissing($reg->transfer_path);

        // Only authorised admins can open the screenshot.
        $this->get(route('admin.registrations.transfer', $reg))->assertRedirect(route('login'));
        $this->actingAs($this->reviewer([]))->get(route('admin.registrations.transfer', $reg))->assertForbidden();
        $this->actingAs($this->reviewer())->get(route('admin.registrations.transfer', $reg))->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->actingAs($this->reviewer())->get(route('admin.registrations.transfer', ['registration' => $reg, 'thumb' => 1]))->assertOk();
        $this->actingAs($this->reviewer())->get(route('admin.registrations.show', $reg))->assertSee(route('admin.registrations.transfer', $reg), false);
    }

    public function test_refused_registration_does_not_keep_the_screenshot(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $leader = $this->leader($c);
        app(RoundService::class)->setRegistration($round, false);

        $this->post('/register', $this->registerForm($leader->unique_code))->assertSessionHas('error_reason', 'registration_closed');
        $this->assertSame([], Storage::disk('local')->allFiles('transfers'));
    }

    public function test_leader_signup_requires_photo_and_starts_pending(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $form = ['name' => 'Mona Adel', 'phone' => '01223334445', 'email' => 'mona@example.com', 'team' => 'Million Team', 'password' => 'secret123', 'password_confirmation' => 'secret123'];

        $this->post('/competition/join', array_diff_key($form, ['team' => 1]))->assertSessionHasErrors('team');
        $this->post('/competition/join', $form)->assertSessionHasErrors('photo');
        $this->post('/competition/join', $form + ['photo' => $this->image('tiny.jpg', 50, 50)])->assertSessionHasErrors('photo');
        $this->assertSame(0, Leader::count());

        $this->post('/competition/join', $form + ['photo' => $this->image('me.jpg', 800, 1000)])->assertRedirect(route('leader.dashboard'));
        $leader = Leader::firstOrFail();
        $this->assertSame('pending', $leader->status);
        $this->assertSame('Million Team', $leader->team);
        $this->assertNotNull($leader->photo);
        Storage::disk('public')->assertExists(substr($leader->photo, 8));
        Storage::disk('public')->assertExists(str_replace('.webp', '-thumb.webp', substr($leader->photo, 8)));
        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get(substr($leader->photo, 8)));
        $this->assertSame($w, $h, 'Leader photos are cropped square.');

        // Pending: QR/code shown but not accepting registrations and hidden from the live board.
        $this->get('/leader')->assertOk()->assertSee($leader->unique_code)->assertSee('Your request is waiting for admin approval');
        $this->getJson('/leader/live')->assertJsonPath('leader_status', 'pending');
        auth()->logout();
        $this->get('/register?ref='.$leader->unique_code)->assertSee('awaiting approval');
        $this->post('/register', $this->registerForm($leader->unique_code))->assertSessionHas('error_reason', 'leader_inactive');
        $this->getJson('/live/competition?v=-1')->assertJsonCount(0, 'leaderboard');
        $this->assertNull(app(\App\Services\ScoreService::class)->rankOf($round, $leader->id));
    }

    public function test_admin_approves_leader_who_then_appears_on_live_board_with_photo(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $this->post('/competition/join', ['name' => 'Mona Adel', 'phone' => '01223334445', 'email' => 'mona@example.com', 'team' => 'Million Team', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'photo' => $this->image('me.jpg', 400, 400)]);
        auth()->logout();
        $leader = Leader::firstOrFail();

        // Reviewer without leader management cannot approve.
        $this->actingAs($this->reviewer([Permission::LeadersView->value]))->post(route('admin.leaders.approve', $leader))->assertForbidden();

        $admin = $this->superAdmin();
        $this->actingAs($admin)->get(route('admin.leaders.index'))->assertOk()->assertSee('Mona Adel')->assertSee(route('admin.leaders.approve', $leader), false);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee('waiting for approval');
        $this->actingAs($admin)->post(route('admin.leaders.approve', $leader))->assertRedirect();

        $leader->refresh();
        $this->assertSame('active', $leader->status);
        $this->assertSame($admin->id, $leader->approved_by);
        $this->assertNotNull($leader->approved_at);
        $this->assertTrue(AuditLog::where('action', 'leader.approved')->where('entity_id', $leader->id)->exists());

        $this->getJson('/live/competition?v=-1')->assertJsonPath('leaderboard.0.name', 'Mona Adel')
            ->assertJsonPath('leaderboard.0.photo', $leader->photoUrl());
        auth()->logout();
        $this->post('/register', $this->registerForm($leader->unique_code))->assertRedirect();
        $this->assertSame(1, Registration::count());

        // Approving twice is refused.
        $this->actingAs($admin)->post(route('admin.leaders.approve', $leader))->assertSessionHas('error');
    }

    public function test_admin_can_reject_a_leader_request(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $leader = app(\App\Services\LeaderService::class)->create($c, ['name' => 'Omar Hany', 'phone' => '01055556666', 'email' => 'omar@example.com']);
        $this->assertSame('pending', $leader->status);

        $this->actingAs($this->superAdmin())->post(route('admin.leaders.reject', $leader), ['note' => 'Duplicate'])->assertRedirect();
        $this->assertSame('rejected', $leader->fresh()->status);
        $this->assertTrue(AuditLog::where('action', 'leader.rejected')->exists());
        $this->get('/register?ref='.$leader->unique_code)->assertSee('This leader is not currently accepting registrations.');
    }

    public function test_leader_can_change_photo(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $leader = $this->leader($c, account: true);
        $this->actingAs($leader->user)->post(route('leader.photo'), ['photo' => $this->image('new.png', 500, 300)])->assertRedirect();
        $path = $leader->fresh()->photo;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists(substr($path, 8));

        $this->actingAs($leader->user)->post(route('leader.photo'), ['photo' => $this->image('newer.png', 500, 500)]);
        Storage::disk('public')->assertMissing(substr($path, 8));
    }

    public function test_login_is_rate_limited_and_inactive_users_cannot_log_in(): void
    {
        $admin = $this->superAdmin();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $admin->email, 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => $admin->email, 'password' => 'secret123'])->assertStatus(429);

        $user = $this->reviewer();
        $user->update(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // ------------------------------------------------------------ authorization

    public function test_admin_area_requires_staff(): void
    {
        $c = $this->competition();
        $leader = $this->leader($c, '01000000009', null, true);

        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs($leader->user)->get('/admin')->assertForbidden();
        $this->actingAs($leader->user)->get('/admin/registrations')->assertForbidden();
    }

    public function test_reviewer_permission_boundaries(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $leader = $this->leader($c);
        $reg = app(RegistrationService::class)->submit($leader->unique_code, $this->participant(1));
        $reviewer = $this->reviewer();

        $this->actingAs($reviewer);
        $this->get('/admin')->assertOk();
        $this->get('/admin/registrations')->assertOk()->assertSee($reg->full_name);
        foreach (['/admin/users', '/admin/settings', '/admin/audit-logs', '/admin/competitions/create', '/admin/leaders/create', '/admin/scanners'] as $url) {
            $this->get($url)->assertForbidden();
        }
        // Critical round actions are super-admin only.
        $this->post(route('admin.rounds.finish', $round))->assertForbidden();
        $this->post(route('admin.rounds.pause', $round))->assertForbidden(); // no timer.control by default
        $this->post(route('admin.rounds.registration', $round), ['open' => 0])->assertForbidden();
        $this->post(route('admin.rounds.store', $c), ['duration_hours' => 1, 'duration_minutes' => 0])->assertForbidden();
        $this->post(route('admin.rounds.recalculate', $round))->assertForbidden();
        $this->assertSame(RoundStatus::Running, $round->fresh()->status);

        // Reviewer without the accept permission cannot accept, even by crafting the request.
        $limited = $this->reviewer([Permission::RegistrationsView->value]);
        $this->actingAs($limited)->postJson(route('admin.registrations.accept', $reg))->assertForbidden();
        $this->assertSame(RegistrationStatus::Pending, $reg->fresh()->status);
    }

    public function test_timer_control_permission_allows_only_pause_resume_adjust(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $timekeeper = $this->reviewer([Permission::TimerControl->value]);

        $this->actingAs($timekeeper)->post(route('admin.rounds.pause', $round))->assertRedirect();
        $this->assertSame(RoundStatus::Paused, $round->fresh()->status);
        $this->post(route('admin.rounds.resume', $round))->assertRedirect();
        $this->post(route('admin.rounds.finish', $round))->assertForbidden();
    }

    // ----------------------------------------------------------- admin workflows

    public function test_accept_and_reject_over_http_update_score_and_audit(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $leader = $this->leader($c);
        $a = app(RegistrationService::class)->submit($leader->unique_code, $this->participant(1));
        $b = app(RegistrationService::class)->submit($leader->unique_code, $this->participant(2));
        $reviewer = $this->reviewer();

        $this->actingAs($reviewer)->postJson(route('admin.registrations.accept', $a))->assertOk()->assertJson(['status' => 'accepted']);
        $this->postJson(route('admin.registrations.reject', $b), ['note' => 'Fake number'])->assertOk()->assertJson(['status' => 'rejected']);
        // Decisions are final.
        $this->postJson(route('admin.registrations.reject', $a))->assertStatus(409);

        $row = app(\App\Services\ScoreService::class)->rankOf($round, $leader->id);
        $this->assertSame(1, (int) $row->accepted);
        $this->assertSame(1, (int) $row->rejected);
        $this->assertSame(2, AuditLog::whereIn('action', ['registration.accepted', 'registration.rejected'])->where('user_id', $reviewer->id)->count());
        $this->assertSame('Fake number', AuditLog::where('action', 'registration.rejected')->first()->metadata['note']);
    }

    public function test_registration_search_and_filters(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $l1 = $this->leader($c, '01000000001', 'Alpha Leader');
        $l2 = $this->leader($c, '01000000002', 'Beta Leader');
        app(RegistrationService::class)->submit($l1->unique_code, $this->participant(1, ['full_name' => 'Karim Fathy']));
        app(RegistrationService::class)->submit($l2->unique_code, $this->participant(2, ['full_name' => 'Nada Samy']));

        $this->actingAs($this->superAdmin());
        $this->get('/admin/registrations?status=all&q=Karim')->assertSee('Karim Fathy')->assertDontSee('Nada Samy');
        $this->get('/admin/registrations?status=all&leader='.$l2->id)->assertSee('Nada Samy')->assertDontSee('Karim Fathy');
        $this->get('/admin/registrations?status=all&q=0111000000')->assertSee('Karim Fathy');
        $this->get('/admin/registrations?status=accepted')->assertDontSee('Karim Fathy');
    }

    public function test_super_admin_timer_and_new_round_over_http(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c, 3600);
        $leader = $this->leader($c);
        $reg = app(RegistrationService::class)->submit($leader->unique_code, $this->participant(1));
        $admin = $this->superAdmin();
        app(RegistrationService::class)->accept($reg, $admin);

        $this->actingAs($admin);
        $before = $round->fresh()->remainingSeconds();
        $this->post(route('admin.rounds.adjust', $round), ['minutes' => 30, 'direction' => 'add'])->assertRedirect();
        $this->assertEqualsWithDelta($before + 1800, $round->fresh()->remainingSeconds(), 2);
        $this->post(route('admin.rounds.adjust', $round), ['minutes' => 10, 'direction' => 'remove'])->assertRedirect();
        $this->assertEqualsWithDelta($before + 1200, $round->fresh()->remainingSeconds(), 2);

        $this->post(route('admin.rounds.pause', $round));
        $paused = $round->fresh()->remainingSeconds();
        $this->travel(10)->minutes();
        $this->assertSame($paused, $round->fresh()->remainingSeconds(), 'Paused timer must not move');

        $this->post(route('admin.rounds.store', $c), ['duration_hours' => 2, 'duration_minutes' => 0, 'start_now' => '1'])->assertRedirect();
        $this->assertSame(RoundStatus::Finished, $round->fresh()->status);
        $new = Round::where('competition_id', $c->id)->where('number', 2)->firstOrFail();
        $this->assertSame(RoundStatus::Running, $new->status);
        // History kept: round 1 still has the accepted registration and score.
        $this->assertSame(1, (int) app(\App\Services\ScoreService::class)->rankOf($round->fresh(), $leader->id)->accepted);
        $this->assertSame(0, (int) app(\App\Services\ScoreService::class)->rankOf($new, $leader->id)->accepted);
        $this->get('/admin/competition')->assertOk()->assertSee('Round 1')->assertSee('Round 2');
        $this->get('/admin/leaderboard?round='.$round->id)->assertOk()->assertSee($leader->name);
    }

    public function test_live_endpoints_report_server_timer(): void
    {
        $c = $this->competition(['public_leaderboard' => true]);
        $round = $this->runningRound($c, 600);
        $this->getJson('/live/competition')->assertOk()
            ->assertJsonPath('round.status', 'running')
            ->assertJsonPath('round.registration_open', true)
            ->assertJsonStructure(['v', 'round' => ['remaining_seconds', 'server_time'], 'leaderboard']);
        $this->travel(11)->minutes();
        $this->getJson('/live/competition')->assertJsonPath('round.status', 'finished')->assertJsonPath('round.remaining_seconds', 0);
        $this->assertSame(RoundStatus::Finished, $round->fresh()->status);
    }

    public function test_admin_can_manage_leaders_and_content(): void
    {
        $this->seed(ContentSeeder::class);
        $c = $this->competition();
        $this->runningRound($c);
        $this->actingAs($this->superAdmin());

        $this->post('/admin/leaders', ['name' => 'Hany Said', 'phone' => '01005556667', 'status' => 'active'])->assertRedirect();
        $leader = Leader::where('name', 'Hany Said')->firstOrFail();
        $this->get(route('admin.leaders.show', $leader))->assertOk()->assertSee($leader->unique_code);
        $this->post('/admin/leaders', ['name' => 'Dup Phone', 'phone' => '+201005556667', 'status' => 'active'])->assertSessionHas('error');

        $member = TeamMember::where('slug', 'rahma-mohamed')->first();
        $this->put(route('admin.team.update', $member), ['name' => $member->name, 'slug' => $member->slug, 'bio' => "First paragraph.\n\n• Point one", 'sort_order' => 1, 'is_active' => '1'])->assertRedirect();
        $this->get('/leaders/rahma-mohamed')->assertSee('First paragraph.')->assertSee('Point one')->assertDontSee('Biography coming soon');

        $this->put('/admin/settings', ['hero' => ['title' => 'A new headline'], 'stats' => ['countries' => 20]])->assertRedirect();
        $this->get('/')->assertSee('A new headline');
    }

    public function test_audit_log_page_is_read_only(): void
    {
        $admin = $this->superAdmin();
        $c = $this->competition();
        app(RoundService::class)->create($c, ['duration_seconds' => 600], $admin);
        
        // Ensure multiple entries render pagination cleanly in both EN and AR
        for ($i = 0; $i < 45; $i++) {
            AuditLog::unguarded(fn () => AuditLog::create([
                'user_id' => $admin->id,
                'actor_name' => 'Admin',
                'action' => 'round.time_added',
                'entity_type' => Round::class,
                'entity_id' => 1,
                'metadata' => ['seconds' => 60],
                'created_at' => now()->subMinutes($i),
            ]));
        }

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get('/admin/audit-logs')->assertOk()->assertSee('Next');
        $this->actingAs($admin)->withSession(['locale' => 'ar'])->get('/admin/audit-logs')->assertOk()->assertSee('التالي');
        
        // No route exists to modify or delete entries.
        $this->delete('/admin/audit-logs/1')->assertNotFound();
        $this->put('/admin/audit-logs/1')->assertNotFound();
        $this->assertGreaterThanOrEqual(1, AuditLog::count());
    }
}
