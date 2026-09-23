<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Competition;
use App\Models\Leader;
use App\Models\Registration;
use App\Services\RegistrationService;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function __construct(private RegistrationService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Registration::class);

        $filters = $request->validate([
            'status' => ['nullable', 'in:all,pending,accepted,rejected'],
            'q' => ['nullable', 'string', 'max:100'],
            'leader' => ['nullable', 'integer'],
            'round' => ['nullable', 'string', 'max:10'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $competition = Competition::active();
        $rounds = $competition ? $competition->rounds()->get() : collect();
        $current = $competition?->currentRound();
        $roundFilter = $filters['round'] ?? ($current?->id ? (string) $current->id : 'all');
        $status = $filters['status'] ?? 'pending';

        $base = Registration::query()
            ->when($competition, fn ($q) => $q->where('competition_id', $competition->id))
            ->when($roundFilter !== 'all', fn ($q) => $q->where('round_id', (int) $roundFilter))
            ->when($filters['leader'] ?? null, fn ($q, $id) => $q->where('leader_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($filters['q'] ?? null, function ($q, $term) {
                $digits = preg_replace('/\D/', '', $term);
                $q->where(function ($w) use ($term, $digits) {
                    $w->where('full_name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%');
                    if (strlen($digits) >= 3) {
                        $w->orWhere('phone_normalized', 'like', '%'.$digits.'%');
                    }
                    if (ctype_digit(ltrim($term, '#'))) {
                        $w->orWhere('id', (int) ltrim($term, '#'));
                    }
                });
            });

        $tabCounts = (clone $base)->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        $registrations = (clone $base)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with(['leader:id,name,unique_code', 'round:id,number,name', 'reviewer:id,name'])
            ->orderBy($status === 'pending' ? 'created_at' : 'id', $status === 'pending' ? 'asc' : 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.registrations.index', [
            'registrations' => $registrations,
            'filters' => $filters + ['status' => $status, 'round' => $roundFilter],
            'tabCounts' => $tabCounts,
            'rounds' => $rounds,
            'leaders' => $competition ? Leader::where('competition_id', $competition->id)->orderBy('name')->get(['id', 'name', 'unique_code']) : collect(),
        ]);
    }

    public function show(Registration $registration)
    {
        $this->authorize('view', $registration);
        $registration->load(['leader', 'round', 'reviewer']);

        $related = Registration::where('competition_id', $registration->competition_id)
            ->where('id', '!=', $registration->id)
            ->where(fn ($q) => $q->where('phone_normalized', $registration->phone_normalized)
                ->when($registration->email_normalized, fn ($w) => $w->orWhere('email_normalized', $registration->email_normalized)))
            ->with('leader:id,name', 'round:id,number,name')->get();

        return view('admin.registrations.show', [
            'registration' => $registration,
            'related' => $related,
            'history' => AuditLog::where('entity_type', 'Registration')->where('entity_id', $registration->id)->latest('id')->get(),
        ]);
    }

    /** Payment screenshot — private file, streamed only to staff allowed to view registrations. */
    public function transfer(Request $request, Registration $registration)
    {
        $this->authorize('view', $registration);
        $path = $registration->transfer_path;
        if ($request->boolean('thumb') && $path) {
            $thumb = str_replace('.webp', '-thumb.webp', $path);
            $path = \Illuminate\Support\Facades\Storage::disk('local')->exists($thumb) ? $thumb : $path;
        }
        abort_unless($path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

        return response()->file(\Illuminate\Support\Facades\Storage::disk('local')->path($path), [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, max-age=600',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    public function accept(Request $request, Registration $registration)
    {
        $this->authorize('accept', $registration);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $this->service->accept($registration, $request->user(), $data['note'] ?? null);

        return $this->done(__('Registration #:id accepted.', ['id' => $registration->id]), ['status' => RegistrationStatus::Accepted->value]);
    }

    public function reject(Request $request, Registration $registration)
    {
        $this->authorize('reject', $registration);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $this->service->reject($registration, $request->user(), $data['note'] ?? null);

        return $this->done(__('Registration #:id rejected.', ['id' => $registration->id]), ['status' => RegistrationStatus::Rejected->value]);
    }
}
