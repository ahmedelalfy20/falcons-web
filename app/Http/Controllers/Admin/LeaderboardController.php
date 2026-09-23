<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Round;
use App\Services\ScoreService;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request, ScoreService $scores)
    {
        abort_unless($request->user()->hasPermission(Permission::LeaderboardView), 403);
        $competition = Competition::active();
        $rounds = $competition ? $competition->rounds()->get() : collect();
        $round = $request->filled('round')
            ? Round::where('competition_id', $competition?->id)->findOrFail($request->integer('round'))
            : $competition?->currentRound();

        return view('admin.leaderboard', [
            'competition' => $competition,
            'rounds' => $rounds,
            'round' => $round,
            'rows' => $round ? $scores->leaderboard($round) : collect(),
        ]);
    }
}
