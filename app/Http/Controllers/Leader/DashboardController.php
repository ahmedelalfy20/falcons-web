<?php

namespace App\Http\Controllers\Leader;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\ImageOptimizer;
use App\Services\LeaderService;
use App\Services\QrCodeService;
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
        $leader = $request->user()->leader->load('competition');
        $round = $this->rounds->syncExpired($leader->competition->currentRound());

        return view('leader.dashboard', [
            'leader' => $leader,
            'competition' => $leader->competition,
            'round' => $round,
            'stats' => $round ? $this->scores->rankOf($round, $leader->id) : null,
            'totalLeaders' => $leader->competition->leaders()->whereNotIn('status', ['pending', 'rejected'])->count(),
            'recent' => $round ? Registration::where('leader_id', $leader->id)->where('round_id', $round->id)->latest()->limit(8)->get(['id', 'full_name', 'status', 'created_at']) : collect(),
            'history' => $leader->competition->rounds()->where('status', 'finished')->get()->map(fn ($r) => ['round' => $r, 'row' => $this->scores->rankOf($r, $leader->id)]),
        ]);
    }

    public function live(Request $request)
    {
        $leader = $request->user()->leader;
        $round = $this->rounds->syncExpired($leader->competition->currentRound());
        if (! $round) {
            return response()->json(['round' => null, 'leader_status' => $leader->status]);
        }
        $row = $this->scores->rankOf($round, $leader->id);

        return response()->json([
            'v' => LiveVersion::get('round.'.$round->id),
            'round' => RoundPayload::make($round),
            'leader_status' => $leader->status,
            'stats' => [
                'rank' => $row?->rank, 'accepted' => (int) ($row?->accepted ?? 0),
                'pending' => (int) ($row?->pending ?? 0), 'rejected' => (int) ($row?->rejected ?? 0),
            ],
        ])->header('Cache-Control', 'no-store');
    }

    public function photo(Request $request, ImageOptimizer $images, LeaderService $leaders)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:min_width=200,min_height=200'],
        ], [
            'photo.dimensions' => __('The photo is too small. Please use one at least 200 × 200 pixels.'),
        ], ['photo' => __('personal photo')]);

        $leader = $request->user()->leader;
        try {
            $path = $images->store($request->file('photo'), 'leaders', 600, 160, 82, square: true)['path'];
        } catch (\RuntimeException) {
            return back()->withErrors(['photo' => __('We could not read this image. Please choose another photo.')]);
        }
        $images->delete($leaders->updatePhoto($leader, $path, $request->user()));

        return back()->with('success', __('Your photo has been updated.'));
    }

    public function qrSvg(Request $request, QrCodeService $qr)
    {
        $leader = $request->user()->leader;

        return response($qr->svg($leader->referralUrl()), 200, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'private, max-age=600']);
    }

    public function qrPng(Request $request, QrCodeService $qr)
    {
        $leader = $request->user()->leader;

        return response($qr->png($leader->referralUrl()), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="falcons-'.strtolower($leader->unique_code).'.png"',
        ]);
    }
}
