<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Registration;
use App\Services\RoundService;
use App\Services\ScoreService;
use App\Support\BoardRow;
use App\Support\LiveVersion;
use App\Support\RoundPayload;
use Illuminate\Http\Request;

/**
 * Polling endpoints. Clients send the last `v` they saw; when nothing changed
 * we answer with a tiny payload so frequent polling stays cheap.
 */
class LiveController extends Controller
{
    public function competition(Request $request, RoundService $rounds, ScoreService $scores)
    {
        $competition = Competition::active();
        $round = $competition ? $rounds->syncExpired($competition->currentRound()) : null;
        if (! $round) {
            return response()->json(['round' => null]);
        }
        $version = LiveVersion::get('round.'.$round->id);
        $payload = ['v' => $version, 'round' => RoundPayload::make($round)];

        if ((int) $request->query('v') !== $version && $competition->config('public_leaderboard')) {
            $meId = $request->user()?->leader?->id;
            $board = BoardRow::board($scores->leaderboard($round, null, true), 20, $meId);
            $payload['leaderboard'] = $board['rows'];
            $payload['me'] = $board['me'];
        }

        return response()->json($payload)->header('Cache-Control', 'no-store');
    }

    public function registration(string $token)
    {
        $registration = Registration::where('public_token', $token)->firstOrFail();

        return response()->json(['status' => $registration->status->value])->header('Cache-Control', 'no-store');
    }
}
