<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Services\GiftService;
use App\Services\RoundService;
use App\Services\ScoreService;
use App\Support\BoardRow;
use Illuminate\Http\Request;

class CompetitionController extends Controller
{
    public function show(Request $request, RoundService $rounds, ScoreService $scores, GiftService $gifts)
    {
        $competition = Competition::active();
        $round = $competition ? $rounds->syncExpired($competition->currentRound()) : null;
        $showBoard = $competition && $competition->config('public_leaderboard');

        $leader = $request->user()?->leader;
        if ($leader && $competition && $leader->competition_id !== $competition->id) {
            $leader = null;
        }

        return view('competition.show', [
            'competition' => $competition,
            'round' => $round,
            'board' => $round && $showBoard ? BoardRow::board($scores->leaderboard($round, null, true), 20, $leader?->id) : ['rows' => [], 'me' => null],
            'account' => $leader ? $gifts->summary($leader) : null,
            'leader' => $leader,
            'showBoard' => $showBoard,
            'gifts' => $competition ? $gifts->ladder() : collect(),
        ]);
    }
}
