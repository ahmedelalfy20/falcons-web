<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Registration;
use App\Services\RoundService;
use App\Services\ScoreService;
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
            $payload['leaderboard'] = $scores->leaderboard($round, 20, true)->map(fn ($r) => [
                'rank' => $r->rank, 'name' => $r->name, 'score' => $r->score, 'photo' => $r->photoUrl(), 'initials' => $r->initials(),
            ]);
        }

        return response()->json($payload)->header('Cache-Control', 'no-store');
    }

    public function registration(string $token)
    {
        $registration = Registration::where('public_token', $token)->firstOrFail();

        return response()->json(['status' => $registration->status->value])->header('Cache-Control', 'no-store');
    }
}
