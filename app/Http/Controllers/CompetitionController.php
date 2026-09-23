<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Services\RoundService;
use App\Services\ScoreService;

class CompetitionController extends Controller
{
    public function show(RoundService $rounds, ScoreService $scores)
    {
        $competition = Competition::active();
        $round = $competition ? $rounds->syncExpired($competition->currentRound()) : null;
        $showBoard = $competition && $competition->config('public_leaderboard');

        return view('competition.show', [
            'competition' => $competition,
            'round' => $round,
            'leaderboard' => $round && $showBoard ? $scores->leaderboard($round, 20, true) : collect(),
            'showBoard' => $showBoard,
        ]);
    }
}
