<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Enums\RegistrationStatus;
use App\Enums\RoundStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\Round;
use App\Services\RoundService;
use App\Services\ScoreService;
use App\Support\LiveVersion;
use App\Support\RoundPayload;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private RoundService $rounds, private ScoreService $scores) {}

    public function index(Request $request)
    {
        return view('admin.dashboard', $this->summary($request) + ['competitionModel' => Competition::active()]);
    }

    public function live(Request $request)
    {
        $version = LiveVersion::get('admin');
        if ((int) $request->query('v') === $version) {
            // Nothing changed — only refresh the (server-authoritative) timer.
            $competition = Competition::active();
            $round = $competition ? $this->rounds->syncExpired($competition->currentRound()) : null;

            return response()->json(['v' => $version, 'unchanged' => true, 'round' => $round ? RoundPayload::make($round) : null])
                ->header('Cache-Control', 'no-store');
        }
        $s = $this->summary($request);

        return response()->json([
            'v' => $version,
            'round' => $s['round'] ? RoundPayload::make($s['round']) : null,
            'counts' => $s['counts'],
            'leaderboard' => $s['leaderboard']?->map(fn ($r) => ['rank' => $r->rank, 'name' => $r->name, 'photo' => $r->photoUrl(), 'initials' => $r->initials(), 'code' => $r->unique_code, 'accepted' => (int) $r->accepted, 'pending' => (int) $r->pending]),
            'recent' => $s['recent']?->map(fn ($r) => [
                'id' => $r->id, 'name' => $r->full_name, 'leader' => $r->leader?->name, 'status' => $r->status->value,
                'status_label' => $r->status->label(), 'ago' => $r->created_at->diffForHumans(), 'url' => route('admin.registrations.show', $r),
            ]),
            'alerts' => $s['alerts'],
        ])->header('Cache-Control', 'no-store');
    }

    private function summary(Request $request): array
    {
        $user = $request->user();
        $competition = Competition::active();
        $round = $competition ? $this->rounds->syncExpired($competition->currentRound()) : null;

        $counts = ['leaders' => 0, 'participants' => 0, 'pending' => 0, 'accepted' => 0, 'rejected' => 0, 'pending_all' => 0, 'pending_leaders' => 0];
        if ($competition) {
            $counts['leaders'] = $competition->leaders()->where('status', 'active')->count();
            $counts['pending_leaders'] = $competition->leaders()->where('status', 'pending')->count();
            $counts['pending_all'] = Registration::where('competition_id', $competition->id)->where('status', 'pending')->count();
        }
        if ($round) {
            $byStatus = Registration::where('round_id', $round->id)->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
            foreach (RegistrationStatus::cases() as $s) {
                $counts[$s->value] = (int) ($byStatus[$s->value] ?? 0);
            }
            $counts['participants'] = array_sum($byStatus->all());
        }

        $canBoard = $user->hasPermission(Permission::LeaderboardView);
        $canRegs = $user->hasPermission(Permission::RegistrationsView);

        return [
            'competition' => $competition,
            'round' => $round,
            'counts' => $counts,
            'leaderboard' => $round && $canBoard ? $this->scores->leaderboard($round, 6) : null,
            'recent' => $round && $canRegs ? Registration::with('leader:id,name')->where('round_id', $round->id)->latest()->limit(7)->get() : null,
            'activity' => $user->can('view-audit-logs') ? AuditLog::latest('id')->limit(8)->get() : collect(),
            'hourly' => $round && $canRegs ? $this->hourly($round) : [],
            'alerts' => $this->alerts($competition, $round, $counts),
            'pastRounds' => $competition ? Round::where('competition_id', $competition->id)->where('status', RoundStatus::Finished->value)->count() : 0,
        ];
    }

    /** Submissions per hour for the last 12 hours (for a compact bar strip). */
    private function hourly(Round $round): array
    {
        $from = now()->subHours(11)->startOfHour();
        $rows = Registration::where('round_id', $round->id)->where('created_at', '>=', $from)->get(['created_at']);
        $buckets = [];
        for ($i = 0; $i < 12; $i++) {
            $h = $from->copy()->addHours($i);
            $buckets[] = ['label' => $h->format('H:00'), 'count' => $rows->filter(fn ($r) => $r->created_at->between($h, $h->copy()->endOfHour()))->count()];
        }

        return $buckets;
    }

    private function alerts(?Competition $competition, ?Round $round, array $counts): array
    {
        $alerts = [];
        if (! $competition) {
            $alerts[] = ['level' => 'warning', 'text' => __('No competition is active. Participants cannot register.')];

            return $alerts;
        }
        if ($counts['pending_leaders'] > 0) {
            $alerts[] = ['level' => 'info', 'text' => trans_choice('{1} 1 new leader is waiting for approval.|[2,*] :n new leaders are waiting for approval.', $counts['pending_leaders'], ['n' => $counts['pending_leaders']]), 'url' => route('admin.leaders.index', ['status' => 'pending']), 'action' => __('Review')];
        }
        if (! $round) {
            $alerts[] = ['level' => 'warning', 'text' => __('The active competition has no rounds yet.')];

            return $alerts;
        }
        if (in_array($round->status, [RoundStatus::Ready, RoundStatus::Draft], true)) {
            $alerts[] = ['level' => 'info', 'text' => __(':round is ready but has not started.', ['round' => $round->displayName()])];
        }
        if ($round->status === RoundStatus::Paused) {
            $alerts[] = ['level' => 'warning', 'text' => __('The timer is paused — registration is closed until it resumes.')];
        }
        if ($round->status === RoundStatus::Running && ! $round->registration_enabled) {
            $alerts[] = ['level' => 'warning', 'text' => __('The round is running but registration is closed.')];
        }
        if ($round->status === RoundStatus::Running && $round->remainingSeconds() < 900) {
            $alerts[] = ['level' => 'danger', 'text' => __('Less than 15 minutes left in :round.', ['round' => $round->displayName()])];
        }
        if ($counts['pending_all'] >= 25) {
            $alerts[] = ['level' => 'info', 'text' => __(':n registrations are waiting for review.', ['n' => $counts['pending_all']])];
        }

        return $alerts;
    }
}
