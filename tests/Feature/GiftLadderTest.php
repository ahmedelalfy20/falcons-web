<?php

namespace Tests\Feature;

use App\Models\Gift;
use App\Models\GiftClaim;
use App\Models\Leader;
use App\Services\GiftService;
use App\Services\RegistrationService;
use App\Services\ScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GiftLadderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    /** Accept $n registrations for the leader in the current round. */
    private function acceptMany(Leader $leader, int $n, int $offset = 0): void
    {
        $regs = app(RegistrationService::class);
        $admin = $this->reviewer();
        for ($i = 1; $i <= $n; $i++) {
            $regs->accept($regs->submit($leader->unique_code, $this->participant($offset + $i)), $admin);
        }
    }

    private function gift(int $points, int $options = 1, string $name = 'Gift'): Gift
    {
        $gift = Gift::create(['name' => $name, 'points_required' => $points, 'is_active' => true]);
        for ($i = 1; $i <= $options; $i++) {
            $gift->options()->create(['image' => "storage/gifts/{$points}-{$i}.webp", 'label' => "Option {$i}", 'sort_order' => $i]);
        }

        return $gift;
    }

    public function test_each_approved_registration_is_worth_ten_points(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $leader = $this->leader($c);

        $this->acceptMany($leader, 3);

        $row = app(ScoreService::class)->rankOf($round, $leader->id);
        $this->assertSame(3, (int) $row->accepted);
        $this->assertSame(30, $row->score);
        $this->assertSame(30, app(ScoreService::class)->totalPoints($leader));
    }

    public function test_points_per_registration_is_configurable(): void
    {
        $c = $this->competition(['points_per_registration' => 5]);
        $round = $this->runningRound($c);
        $leader = $this->leader($c);
        $this->acceptMany($leader, 2);

        $this->assertSame(10, app(ScoreService::class)->rankOf($round, $leader->id)->score);
    }

    public function test_points_accumulate_and_unlock_gifts_in_order(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $leader = $this->leader($c);
        $this->gift(30, 1, 'Mug');
        $this->gift(60, 2, 'Headphones');
        $this->gift(120, 1, 'Watch');

        $this->acceptMany($leader, 4); // 40 points
        $p = app(GiftService::class)->progressFor($leader);

        $this->assertSame(40, $p['total']);
        $this->assertSame(1, $p['unlockedCount']);
        $this->assertSame('Headphones', $p['next']->name);
        $this->assertSame(20, $p['remaining']);
        $this->assertSame(33, $p['percent']); // 10 of the 30 points between 30 and 60

        $this->acceptMany($leader, 2, 10); // 60 points — total stays, next gift opens
        $p = app(GiftService::class)->progressFor($leader->fresh());
        $this->assertSame(60, $p['total']);
        $this->assertSame(2, $p['unlockedCount']);
        $this->assertSame('Watch', $p['next']->name);
    }

    public function test_leader_claims_unlocked_gift_and_picks_an_option(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $leader = $this->leader($c, '01000000001', null, true);
        $mug = $this->gift(30);
        $choice = $this->gift(60, 2);
        $this->acceptMany($leader, 6);
        $this->actingAs($leader->user);

        $this->get('/leader')->assertOk()->assertSee('60')->assertSee(__('Collect points, unlock gifts'));

        $this->post("/leader/gifts/{$mug->id}/claim")->assertRedirect();
        $this->assertDatabaseHas('gift_claims', ['leader_id' => $leader->id, 'gift_id' => $mug->id, 'gift_option_id' => $mug->options->first()->id, 'status' => 'requested']);

        // A gift with options needs a valid pick.
        $this->post("/leader/gifts/{$choice->id}/claim")->assertSessionHasErrors('gift');
        $second = $choice->options->last();
        $this->post("/leader/gifts/{$choice->id}/claim", ['option' => $second->id])->assertRedirect();
        $this->assertSame($second->id, GiftClaim::where('gift_id', $choice->id)->value('gift_option_id'));

        // Changing the pick updates the same request.
        $first = $choice->options->first();
        $this->post("/leader/gifts/{$choice->id}/claim", ['option' => $first->id]);
        $this->assertSame(1, GiftClaim::where('gift_id', $choice->id)->count());
        $this->assertSame($first->id, GiftClaim::where('gift_id', $choice->id)->value('gift_option_id'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'gift.claimed']);
    }

    public function test_locked_or_delivered_gifts_cannot_be_claimed(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $leader = $this->leader($c, '01000000001', null, true);
        $mug = $this->gift(30);
        $big = $this->gift(300);
        $this->acceptMany($leader, 3);
        $this->actingAs($leader->user);

        $this->post("/leader/gifts/{$big->id}/claim")->assertSessionHasErrors('gift');
        $this->assertDatabaseMissing('gift_claims', ['gift_id' => $big->id]);

        $this->post("/leader/gifts/{$mug->id}/claim");
        GiftClaim::where('gift_id', $mug->id)->update(['status' => 'delivered']);
        $this->post("/leader/gifts/{$mug->id}/claim")->assertSessionHasErrors('gift');
    }

    public function test_admin_creates_gift_with_photo_options_and_marks_claim_delivered(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post('/admin/gifts', [
            'name' => 'Headphones', 'name_ar' => 'سماعة', 'points_required' => 60, 'is_active' => '1',
            'new_options' => [
                ['image' => $this->image('a.jpg', 800, 600), 'label' => 'Black'],
                ['image' => $this->image('b.jpg', 800, 600), 'label' => 'White'],
            ],
        ])->assertRedirect(route('admin.gifts.index'));

        $gift = Gift::with('options')->firstOrFail();
        $this->assertSame(60, $gift->points_required);
        $this->assertCount(2, $gift->options);
        $this->assertTrue($gift->hasChoice());
        Storage::disk('public')->assertExists(substr($gift->options->first()->image, 8));

        // A gift without photos is refused.
        $this->post('/admin/gifts', ['name' => 'Empty', 'points_required' => 10])->assertSessionHasErrors('new_options');

        // Remove one option on edit.
        $this->put("/admin/gifts/{$gift->id}", [
            'name' => 'Headphones', 'points_required' => 70, 'is_active' => '1',
            'options' => [$gift->options[0]->id => ['label' => 'Black'], $gift->options[1]->id => ['remove' => '1']],
        ])->assertRedirect();
        $this->assertCount(1, $gift->fresh()->options);
        $this->assertSame(70, $gift->fresh()->points_required);

        $leader = $this->leader($c);
        $claim = GiftClaim::create(['leader_id' => $leader->id, 'gift_id' => $gift->id, 'gift_option_id' => $gift->options[0]->id]);
        $this->get('/admin/gifts')->assertOk()->assertSee($leader->name);
        $this->post("/admin/gift-claims/{$claim->id}/deliver")->assertRedirect();
        $this->assertSame('delivered', $claim->fresh()->status);
        $this->assertSame($admin->id, $claim->fresh()->delivered_by);

        $this->delete("/admin/gifts/{$gift->id}")->assertRedirect(route('admin.gifts.index'));
        $this->assertDatabaseCount('gifts', 0);
        $this->assertDatabaseCount('gift_options', 0);
    }

    public function test_reviewers_cannot_manage_gifts(): void
    {
        $this->competition();
        $this->actingAs($this->reviewer())->get('/admin/gifts')->assertForbidden();
    }

    public function test_public_competition_page_shows_the_gift_ladder(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $this->gift(30, 1, 'Falcons Mug');
        $this->gift(60, 2, 'Headphones');

        $this->get('/competition')->assertOk()
            ->assertSee('Falcons Mug')->assertSee('Headphones')
            ->assertSee(__('Choose 1 of :n', ['n' => 2]))
            ->assertSee(__('Approved = :n points', ['n' => 10]));
    }

    public function test_leaderboard_shows_top_twenty_and_the_signed_in_leaders_own_row(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $leaders = collect(range(1, 22))->map(fn ($i) => $this->leader($c, '0100000'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), "Leader {$i}", $i === 22));
        // Leader 1 gets the most points, leader 22 none.
        $regs = app(RegistrationService::class);
        $admin = $this->reviewer();
        $n = 0;
        foreach ($leaders->take(3) as $i => $leader) {
            for ($k = 0; $k < 3 - $i; $k++) {
                $regs->accept($regs->submit($leader->unique_code, $this->participant(++$n)), $admin);
            }
        }

        $json = $this->getJson('/live/competition')->assertOk()->json();
        $this->assertCount(20, $json['leaderboard']);
        $this->assertSame(['rank' => 1, 'accepted' => 3, 'score' => 30], array_intersect_key($json['leaderboard'][0], array_flip(['rank', 'score', 'accepted'])));
        $this->assertNull($json['me']);

        $me = $leaders->last();
        $this->actingAs($me->user);
        $json = $this->getJson('/live/competition')->json();
        $this->assertSame('Leader 22', $json['me']['name']);
        $this->assertTrue($json['me']['me']);
        $this->assertSame(22, $json['me']['rank']);

        $this->getJson('/leader/live')->assertOk()->assertJsonCount(20, 'leaderboard')->assertJsonPath('me.rank', 22);
    }

    public function test_leader_sees_account_details_across_the_site(): void
    {
        $c = $this->competition();
        $this->runningRound($c);
        $leader = $this->leader($c, '01000000001', 'Omar Hassan', true);
        $this->gift(30, 1, 'Mug');
        $this->gift(60, 1, 'Headphones');
        $this->acceptMany($leader, 4); // 40 points
        $this->actingAs($leader->user);

        // Header chip + account card on public pages.
        $this->get('/competition')->assertOk()
            ->assertSee(__('Your account'))->assertSee(__('Next gift'))->assertSee('Headphones')
            ->assertSee(__(':n points left', ['n' => 20]), false);
        $this->get('/')->assertOk()->assertSee(__('My dashboard'));

        // Dashboard: tabs, points history.
        $this->get('/leader')->assertOk()
            ->assertSee(__('Points history'))->assertSee(__('Approved registration'))->assertSee('+10');
    }
}
