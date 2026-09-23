<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Course;
use App\Models\GalleryImage;
use App\Models\Scanner;
use App\Models\TeamMember;
use App\Services\RoundService;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home(RoundService $rounds)
    {
        $competition = Competition::active();
        $round = $competition ? $rounds->syncExpired($competition->currentRound()) : null;

        return view('pages.home', [
            'scanners' => Scanner::visible()->get(),
            'team' => TeamMember::visible()->get(),
            'courses' => Course::visible()->get()->groupBy('track'),
            'gallery' => GalleryImage::collection('academy')->get(),
            'companies' => \App\Models\Company::visible()->get(),
            'competition' => $competition,
            'round' => $round,
        ]);
    }

    /** "Register now": download the Falcons app from Google Play or the App Store. */
    public function app(\App\Services\QrCodeService $qr)
    {
        $stores = array_filter([
            'play' => site('app.play_url', null, false),
            'appstore' => site('app.appstore_url', null, false),
        ]);

        return view('pages.app', [
            'stores' => $stores,
            'qr' => array_map(fn ($url) => $qr->svg($url, 180), $stores),
        ]);
    }

    public function scanner(Scanner $scanner)
    {
        abort_unless($scanner->is_active, 404);

        return view('pages.scanner', [
            'scanner' => $scanner,
            'others' => Scanner::visible()->where('id', '!=', $scanner->id)->get(),
        ]);
    }

    public function member(TeamMember $member)
    {
        abort_unless($member->is_active, 404);
        $team = TeamMember::visible()->get();
        $index = $team->search(fn ($m) => $m->id === $member->id);

        return view('pages.member', [
            'member' => $member,
            'previous' => $team->count() > 1 ? $team[($index - 1 + $team->count()) % $team->count()] : null,
            'next' => $team->count() > 1 ? $team[($index + 1) % $team->count()] : null,
            'team' => $team,
        ]);
    }

    public function locale(Request $request, string $locale)
    {
        $request->session()->put('locale', $locale);
        $back = url()->previous();

        return redirect()->to(str_starts_with($back, url('/')) ? $back : route('home'));
    }
}
