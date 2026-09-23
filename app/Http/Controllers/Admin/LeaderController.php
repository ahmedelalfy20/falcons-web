<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Leader;
use App\Models\Registration;
use App\Services\ImageOptimizer;
use App\Services\LeaderService;
use App\Services\QrCodeService;
use App\Services\ScoreService;
use App\Support\PhoneNumber;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class LeaderController extends Controller
{
    public function __construct(private LeaderService $leaders, private ScoreService $scores, private ImageOptimizer $images) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Leader::class);
        $competition = Competition::active();
        $round = $competition?->currentRound();
        $q = trim((string) $request->query('q'));
        $status = $request->query('status');
        if ($status === null && $competition && Leader::where('competition_id', $competition->id)->where('status', 'pending')->exists()) {
            $status = 'pending';
        }

        $board = $round ? $this->scores->leaderboard($round)->keyBy('id') : collect();

        $leaders = Leader::query()
            ->when($competition, fn ($x) => $x->where('competition_id', $competition->id))
            ->when($q !== '', function ($x) use ($q) {
                $digits = preg_replace('/\D/', '', $q);
                $x->where(function ($w) use ($q, $digits) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('unique_code', 'like', '%'.strtoupper($q).'%')
                        ->orWhere('email', 'like', "%{$q}%");
                    if (strlen($digits) >= 3) {
                        $w->orWhere('phone_normalized', 'like', "%{$digits}%");
                    }
                });
            })
            ->when(in_array($status, Leader::STATUSES, true), fn ($x) => $x->where('status', $status))
            ->withCount('registrations')
            ->when($status === 'pending', fn ($x) => $x->orderBy('created_at'), fn ($x) => $x->orderBy('name'))
            ->paginate(25)->withQueryString();

        $statusCounts = $competition
            ? Leader::where('competition_id', $competition->id)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status')
            : collect();

        return view('admin.leaders.index', compact('leaders', 'board', 'round', 'q', 'status', 'competition', 'statusCounts'));
    }

    public function create()
    {
        $this->authorize('create', Leader::class);

        return view('admin.leaders.form', ['leader' => new Leader(['status' => 'active'])]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Leader::class);
        $competition = Competition::active() ?? abort(422, __('There is no active competition right now.'));
        $data = $this->validated($request, null);
        $password = $request->boolean('create_account') ? $data['password'] : null;
        unset($data['photo']);
        if ($request->hasFile('photo')) {
            $data['photo'] = $this->storePhoto($request);
        }
        $leader = $this->leaders->create($competition, $data, $password, $request->user(), $data['status']);

        return redirect()->route('admin.leaders.show', $leader)->with('success', __('Leader created. Referral code: :code', ['code' => $leader->unique_code]));
    }

    public function show(Leader $leader)
    {
        $this->authorize('view', $leader);
        $leader->load('competition', 'user');
        $rounds = $leader->competition->rounds()->get();
        $perRound = $rounds->map(fn ($r) => ['round' => $r, 'row' => $this->scores->rankOf($r, $leader->id)]);

        return view('admin.leaders.show', [
            'leader' => $leader,
            'perRound' => $perRound,
            'recent' => Registration::where('leader_id', $leader->id)->with('round:id,number,name')->latest()->limit(10)->get(),
        ]);
    }

    public function edit(Leader $leader)
    {
        $this->authorize('update', $leader);

        return view('admin.leaders.form', compact('leader'));
    }

    public function update(Request $request, Leader $leader)
    {
        $this->authorize('update', $leader);
        $data = $this->validated($request, $leader);
        unset($data['photo']);
        $old = null;
        if ($request->hasFile('photo')) {
            $old = $leader->photo;
            $data['photo'] = $this->storePhoto($request);
        } elseif ($request->boolean('remove_photo')) {
            $old = $leader->photo;
            $data['photo'] = null;
        }
        $this->leaders->update($leader, $data, $request->user());
        $this->images->delete($old);

        return redirect()->route('admin.leaders.show', $leader)->with('success', __('Leader updated.'));
    }

    public function approve(Request $request, Leader $leader)
    {
        $this->authorize('review', $leader);
        $this->leaders->approve($leader, $request->user());

        return back()->with('success', __(':name is approved and now appears on the live leaderboard.', ['name' => $leader->name]));
    }

    public function reject(Request $request, Leader $leader)
    {
        $this->authorize('review', $leader);
        $note = $request->validate(['note' => ['nullable', 'string', 'max:500']])['note'] ?? null;
        $this->leaders->reject($leader, $request->user(), $note);

        return back()->with('success', __('The request from :name was rejected.', ['name' => $leader->name]));
    }

    public function destroy(Request $request, Leader $leader)
    {
        $this->authorize('delete', $leader);
        $this->leaders->delete($leader, $request->user());

        return redirect()->route('admin.leaders.index')->with('success', __('Leader deleted.'));
    }

    public function qr(Leader $leader, QrCodeService $qr)
    {
        $this->authorize('view', $leader);

        return response($qr->svg($leader->referralUrl()), 200, ['Content-Type' => 'image/svg+xml']);
    }

    private function storePhoto(Request $request): string
    {
        try {
            return $this->images->store($request->file('photo'), 'leaders', 600, 160, 82, square: true)['path'];
        } catch (\RuntimeException) {
            throw \Illuminate\Validation\ValidationException::withMessages(['photo' => __('We could not read this image. Please choose another photo.')]);
        }
    }

    private function validated(Request $request, ?Leader $leader): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'phone' => ['required', 'string', 'max:25', function (string $a, mixed $v, Closure $fail) {
                if (! PhoneNumber::isValid($v)) {
                    $fail(__('Please enter a valid phone number.'));
                }
            }],
            'email' => [$request->boolean('create_account') ? 'required' : 'nullable', 'email:rfc', 'max:190', $leader ? null : 'unique:users,email'],
            'status' => ['required', 'in:'.implode(',', Leader::STATUSES)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_photo' => ['sometimes', 'boolean'],
            'create_account' => ['sometimes', 'boolean'],
            'password' => [$request->boolean('create_account') && ! $leader ? 'required' : 'nullable', Password::defaults()],
        ]);
    }
}
