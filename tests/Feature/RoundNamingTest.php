<?php

namespace Tests\Feature;

use App\Models\Round;
use App\Services\RoundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoundNamingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_rounds_continue_or_restart_numbering_and_can_have_names(): void
    {
        $c = $this->competition();
        $admin = $this->superAdmin();
        $rounds = app(RoundService::class);
        $r1 = $rounds->create($c, []);
        $r2 = $rounds->startNewRound($c, 3600, false, $admin);
        $this->assertSame('Round 2', $r2->displayName());

        $this->actingAs($admin)->post("/admin/competitions/{$c->id}/rounds", [
            'duration_hours' => 1, 'duration_minutes' => 0, 'restart_numbering' => '1', 'name_ar' => 'جولة رمضان',
        ])->assertRedirect();
        $r3 = Round::where('competition_id', $c->id)->orderByDesc('number')->first();
        $this->assertSame(3, $r3->number);           // internal order kept
        $this->assertSame(1, $r3->shownNumber());    // what visitors see
        $this->assertSame('Round 1', $r3->displayName());
        app()->setLocale('ar');
        $this->assertSame('جولة رمضان', $r3->displayName());
        app()->setLocale('en');

        $r4 = $rounds->startNewRound($c, 3600, false, $admin);
        $this->assertSame('Round 2', $r4->displayName()); // continues from the restart
    }

    public function test_admin_renames_a_round_and_resets_its_number(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $round->update(['number' => 19, 'display_number' => 19]);

        $this->actingAs($this->superAdmin())->put("/admin/rounds/{$round->id}/details", [
            'display_number' => 1, 'name' => 'Launch round', 'name_ar' => '',
        ])->assertRedirect()->assertSessionHas('success');

        $round->refresh();
        $this->assertSame(1, $round->shownNumber());
        $this->assertSame('Launch round', $round->displayName());
        $this->assertNull($round->name_ar);
        $this->get('/competition?lang=en')->assertSee('Launch round');

        $this->put("/admin/rounds/{$round->id}/details", ['display_number' => 1, 'name' => ''])->assertRedirect();
        $this->get('/competition?lang=en')->assertSee('Round 1');
        $this->assertDatabaseHas('audit_logs', ['action' => 'round.renamed']);
    }

    public function test_reviewers_cannot_rename_rounds(): void
    {
        $c = $this->competition();
        $round = $this->runningRound($c);
        $this->actingAs($this->reviewer())->put("/admin/rounds/{$round->id}/details", ['display_number' => 1])->assertForbidden();
    }
}
