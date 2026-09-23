<?php

namespace App\Http\Controllers;

use App\Models\FreeVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Free courses are unlocked with an invite code that is verified on the
 * server (the old site shipped the code inside the JavaScript bundle and
 * served the videos publicly). Videos are streamed only to unlocked sessions.
 */
class FreeCoursesController extends Controller
{
    public function index(Request $request)
    {
        $unlocked = $request->session()->get('free_courses_unlocked', false);

        return view('pages.free-courses', [
            'unlocked' => $unlocked,
            'videos' => $unlocked ? FreeVideo::where('is_active', true)->orderBy('sort_order')->get() : collect(),
            'count' => FreeVideo::where('is_active', true)->count(),
            'justUnlocked' => $request->session()->pull('free_courses_welcome', false),
        ]);
    }

    public function unlock(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:64']]);
        $hash = site('free_courses.invite_code_hash', null, false);

        if (! site('free_courses.enabled', true, false) || ! $hash || ! Hash::check(trim($data['code']), $hash)) {
            return back()->withErrors(['code' => __('Invalid invite code')])->onlyInput('code');
        }
        $request->session()->put('free_courses_unlocked', true);
        $request->session()->flash('free_courses_welcome', true);

        return redirect()->route('free-courses');
    }

    public function stream(Request $request, FreeVideo $video)
    {
        abort_unless($request->session()->get('free_courses_unlocked') && $video->is_active, 403);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($video->video_path), 404);

        // BinaryFileResponse supports HTTP Range requests for seeking.
        return response()->file($disk->path($video->video_path), [
            'Content-Type' => 'video/mp4',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
