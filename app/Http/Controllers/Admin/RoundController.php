<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Round;
use App\Services\AuditLogger;
use App\Services\RoundService;
use App\Services\ScoreService;
use Illuminate\Http\Request;

class RoundController extends Controller
{
    public function __construct(private RoundService $rounds) {}

    /** "Start New Round": finishes the live round (results kept) and opens the next one. */
    public function store(Request $request, Competition $competition)
    {
        $this->authorize('create', Round::class);
        $data = $request->validate([
            'duration_hours' => ['required', 'integer', 'min:0', 'max:720'],
            'duration_minutes' => ['required', 'integer', 'min:0', 'max:59'],
            'start_now' => ['sometimes', 'boolean'],
        ]);
        $seconds = $data['duration_hours'] * 3600 + $data['duration_minutes'] * 60;
        if ($seconds < 60) {
            return back()->withErrors(['duration_minutes' => __('A round must last at least one minute.')]);
        }
        $round = $this->rounds->startNewRound($competition, $seconds, $request->boolean('start_now'), $request->user());

        return redirect()->route('admin.competition.index')->with('success', __(':round created.', ['round' => $round->displayName()]));
    }

    public function show(Round $round, ScoreService $scores)
    {
        abort_unless(request()->user()->isStaff(), 403);
        $round->load('competition');
        $byStatus = $round->registrations()->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        return view('admin.competition.round', [
            'round' => $round,
            'rows' => request()->user()->can('leaderboard.view') ? $scores->leaderboard($round) : collect(),
            'byStatus' => $byStatus,
        ]);
    }

    public function start(Request $request, Round $round)
    {
        $this->authorize('start', $round);
        $this->rounds->start($round, $request->user());

        return $this->done(__('Round started.'));
    }

    public function pause(Request $request, Round $round)
    {
        $this->authorize('adjustTimer', $round);
        $this->rounds->pause($round, $request->user());

        return $this->done(__('Timer paused.'));
    }

    public function resume(Request $request, Round $round)
    {
        $this->authorize('adjustTimer', $round);
        $this->rounds->resume($round, $request->user());

        return $this->done(__('Timer resumed.'));
    }

    public function finish(Request $request, Round $round)
    {
        $this->authorize('finish', $round);
        $this->rounds->finish($round, $request->user());

        return $this->done(__('Round finished. Results are saved.'));
    }

    public function adjust(Request $request, Round $round)
    {
        $this->authorize('adjustTimer', $round);
        $data = $request->validate([
            'minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'direction' => ['required', 'in:add,remove'],
        ]);
        $seconds = $data['minutes'] * 60 * ($data['direction'] === 'add' ? 1 : -1);
        $this->rounds->adjustTime($round, $seconds, $request->user());

        return $this->done($data['direction'] === 'add'
            ? __('Added :m minutes.', ['m' => $data['minutes']])
            : __('Removed :m minutes.', ['m' => $data['minutes']]));
    }

    public function registration(Request $request, Round $round)
    {
        $this->authorize('toggleRegistration', $round);
        $open = $request->validate(['open' => ['required', 'boolean']])['open'];
        $this->rounds->setRegistration($round, (bool) $open, $request->user());

        return $this->done($open ? __('Registration opened.') : __('Registration closed.'));
    }

    public function recalculate(Request $request, Round $round, ScoreService $scores, AuditLogger $audit)
    {
        $this->authorize('super-admin');
        $n = $scores->recalculateRound($round);
        $audit->log('round.scores_recalculated', $round, ['leaders' => $n]);

        return $this->done(__('Scores recalculated from accepted registrations.'));
    }
}
