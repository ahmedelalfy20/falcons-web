<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view-audit-logs');
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:64'],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $logs = AuditLog::query()
            ->when($filters['action'] ?? null, fn ($q, $a) => $q->where('action', 'like', $a.'%'))
            ->when($filters['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('actor_name', 'like', "%{$t}%")->orWhere('entity_id', (int) ltrim($t, '#'))))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('id')
            ->paginate(40)->withQueryString();

        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action')
            ->map(fn ($a) => explode('.', $a)[0])->unique()->values();

        return view('admin.audit', compact('logs', 'filters', 'actions'));
    }
}
