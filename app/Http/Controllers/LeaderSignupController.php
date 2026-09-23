<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Http\Requests\LeaderSignupRequest;
use App\Models\Competition;
use App\Services\ImageOptimizer;
use App\Services\LeaderService;
use Illuminate\Support\Facades\Auth;

class LeaderSignupController extends Controller
{
    public function create()
    {
        $competition = Competition::active();

        return view('competition.leader-signup', [
            'competition' => $competition,
            'open' => $competition && $competition->config('leader_self_registration'),
        ]);
    }

    public function store(LeaderSignupRequest $request, LeaderService $leaders, ImageOptimizer $images)
    {
        $competition = Competition::active();
        if (! $competition) {
            throw BusinessRuleException::make('competition_inactive');
        }
        if (! $competition->config('leader_self_registration')) {
            throw BusinessRuleException::make('leader_registration_disabled');
        }

        try {
            $photo = $images->store($request->file('photo'), 'leaders', 600, 160, 82, square: true)['path'];
        } catch (\RuntimeException) {
            return back()->withInput()->withErrors(['photo' => __('We could not read this image. Please choose another photo.')]);
        }

        try {
            $leader = $leaders->create($competition, $request->safe()->only(['name', 'phone', 'email']) + ['photo' => $photo], $request->validated('password'));
        } catch (\Throwable $e) {
            $images->delete($photo);
            throw $e;
        }

        Auth::login($leader->user);
        $request->session()->regenerate();

        return redirect()->route('leader.dashboard')->with('success', $leader->isActive()
            ? __('Welcome aboard! Your referral code and QR are ready to share.')
            : __('Your request has been sent! An admin will review it shortly — you will appear on the live leaderboard as soon as you are approved.'));
    }
}
