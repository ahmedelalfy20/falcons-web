<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Services\AuditLogger;
use App\Services\RoundService;
use App\Services\ScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompetitionController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    /** Competition control room: current round timer + round history. */
    public function index(Request $request, RoundService $rounds, ScoreService $scores)
    {
        abort_unless($request->user()->isStaff(), 403);
        $competition = Competition::active();
        $round = $competition ? $rounds->syncExpired($competition->currentRound()) : null;

        return view('admin.competition.index', [
            'competition' => $competition,
            'round' => $round,
            'history' => $competition ? $competition->rounds()->withCount([
                'registrations', 'registrations as accepted_count' => fn ($q) => $q->where('status', 'accepted'),
            ])->get()->reverse() : collect(),
            'competitions' => $request->user()->can('manage-competitions') ? Competition::latest('id')->get() : collect(),
            'winnerFor' => fn ($r) => $scores->leaderboard($r, 1)->first(),
        ]);
    }

    public function create()
    {
        $this->authorize('manage-competitions');

        return view('admin.competition.form', ['competition' => new Competition(['status' => CompetitionStatus::Draft, 'configuration' => Competition::DEFAULT_CONFIG])]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-competitions');
        $data = $this->validated($request);
        $competition = DB::transaction(function () use ($data) {
            $this->assertSingleActive($data['status']);
            $c = Competition::create($data);
            $this->audit->log('competition.created', $c, ['status' => $c->status->value]);

            return $c;
        });

        return redirect()->route('admin.competition.index')->with('success', __('Competition ":name" created.', ['name' => $competition->name]));
    }

    public function edit(Competition $competition)
    {
        $this->authorize('manage-competitions');

        return view('admin.competition.form', compact('competition'));
    }

    public function update(Request $request, Competition $competition)
    {
        $this->authorize('manage-competitions');
        $data = $this->validated($request);
        DB::transaction(function () use ($competition, $data) {
            $this->assertSingleActive($data['status'], $competition->id);
            $before = ['status' => $competition->status->value, 'configuration' => $competition->fullConfig()];
            $competition->update($data);
            $this->audit->log('competition.updated', $competition, ['before' => $before, 'after' => ['status' => $competition->status->value, 'configuration' => $competition->fullConfig()]]);
        });

        return redirect()->route('admin.competition.index')->with('success', __('Competition settings saved.'));
    }

    private function validated(Request $request): array
    {
        $v = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'name_ar' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'in:draft,active,archived'],
            'duplicate_scope' => ['required', 'in:round,competition'],
            'default_round_minutes' => ['required', 'integer', 'min:1', 'max:43200'],
            'require_email' => ['sometimes', 'boolean'],
            'require_transfer_proof' => ['sometimes', 'boolean'],
            'collect_city' => ['sometimes', 'boolean'],
            'leader_self_registration' => ['sometimes', 'boolean'],
            'leader_auto_approve' => ['sometimes', 'boolean'],
            'public_leaderboard' => ['sometimes', 'boolean'],
        ]);

        return [
            'name' => $v['name'],
            'name_ar' => $v['name_ar'] ?? null,
            'status' => $v['status'],
            'configuration' => [
                'duplicate_scope' => $v['duplicate_scope'],
                'default_round_minutes' => (int) $v['default_round_minutes'],
                'require_email' => $request->boolean('require_email'),
                'require_transfer_proof' => $request->boolean('require_transfer_proof'),
                'collect_city' => $request->boolean('collect_city'),
                'leader_self_registration' => $request->boolean('leader_self_registration'),
                'leader_auto_approve' => $request->boolean('leader_auto_approve'),
                'public_leaderboard' => $request->boolean('public_leaderboard'),
            ],
        ];
    }

    private function assertSingleActive(string $status, ?int $ignoreId = null): void
    {
        if ($status !== CompetitionStatus::Active->value) {
            return;
        }
        $other = Competition::where('status', CompetitionStatus::Active->value)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->lockForUpdate()->first();
        if ($other) {
            throw ValidationException::withMessages(['status' => __('":name" is already active. Archive it first — only one competition can be active at a time.', ['name' => $other->name])]);
        }
    }
}
