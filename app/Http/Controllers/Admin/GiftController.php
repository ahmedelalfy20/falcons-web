<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gift;
use App\Models\GiftClaim;
use App\Models\GiftOption;
use App\Services\AuditLogger;
use App\Services\GiftService;
use App\Services\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Leader gift ladder: gifts, their photo options, and leaders' claims. */
class GiftController extends Controller
{
    private const MAX_OPTIONS = 4;

    public function __construct(private ImageOptimizer $images, private AuditLogger $audit, private GiftService $gifts) {}

    public function index(Request $request)
    {
        $this->authorize('manage-competitions');
        $status = $request->query('status', 'requested');

        return view('admin.gifts.index', [
            'gifts' => Gift::with('options')->withCount('claims')->orderBy('points_required')->orderBy('id')->get(),
            'claims' => GiftClaim::with(['leader', 'gift', 'option'])
                ->when(in_array($status, ['requested', 'delivered'], true), fn ($q) => $q->where('status', $status))
                ->latest('updated_at')->limit(100)->get(),
            'status' => $status,
            'requestedCount' => GiftClaim::where('status', 'requested')->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('manage-competitions');
        $last = (int) Gift::max('points_required');

        return view('admin.gifts.form', ['gift' => new Gift(['is_active' => true, 'points_required' => $last + 30])]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-competitions');
        $data = $this->validated($request, null);
        $gift = DB::transaction(function () use ($request, $data) {
            $gift = Gift::create($data);
            $this->syncOptions($request, $gift);

            return $gift;
        });
        $this->audit->log('gift.created', $gift, ['name' => $gift->name, 'points' => $gift->points_required]);

        return redirect()->route('admin.gifts.index')->with('success', __('Gift ":name" added.', ['name' => $gift->name]));
    }

    public function edit(Gift $gift)
    {
        $this->authorize('manage-competitions');

        return view('admin.gifts.form', ['gift' => $gift->load('options')]);
    }

    public function update(Request $request, Gift $gift)
    {
        $this->authorize('manage-competitions');
        $gift->load('options');
        $data = $this->validated($request, $gift);
        DB::transaction(function () use ($request, $gift, $data) {
            $gift->update($data);
            $this->syncOptions($request, $gift);
        });
        $this->audit->log('gift.updated', $gift, ['name' => $gift->name, 'points' => $gift->points_required]);

        return redirect()->route('admin.gifts.index')->with('success', __('Gift saved.'));
    }

    public function destroy(Gift $gift)
    {
        $this->authorize('manage-competitions');
        foreach ($gift->options as $option) {
            $this->images->delete($option->image);
        }
        $this->audit->log('gift.deleted', $gift, ['name' => $gift->name]);
        $gift->delete();

        return redirect()->route('admin.gifts.index')->with('success', __('Gift removed.'));
    }

    public function deliver(Request $request, GiftClaim $claim)
    {
        $this->authorize('manage-competitions');
        $this->gifts->markDelivered($claim, $request->user(), ! $request->boolean('undo'));

        return back()->with('success', $request->boolean('undo') ? __('Marked as not delivered.') : __('Gift marked as delivered.'));
    }

    private function validated(Request $request, ?Gift $gift): array
    {
        $v = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'name_ar' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'description_ar' => ['nullable', 'string', 'max:500'],
            'points_required' => ['required', 'integer', 'min:1', 'max:1000000'],
            'options' => ['array'],
            'options.*.label' => ['nullable', 'string', 'max:120'],
            'options.*.label_ar' => ['nullable', 'string', 'max:120'],
            'options.*.remove' => ['nullable', 'boolean'],
            'new_options' => ['array', 'max:'.self::MAX_OPTIONS],
            'new_options.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'new_options.*.label' => ['nullable', 'string', 'max:120'],
            'new_options.*.label_ar' => ['nullable', 'string', 'max:120'],
        ], [], [
            'new_options.*.image' => __('gift photo'),
        ]);

        $kept = $gift ? $gift->options->reject(fn ($o) => (bool) ($request->input("options.{$o->id}.remove")))->count() : 0;
        $added = collect($request->file('new_options', []))->filter(fn ($row) => isset($row['image']))->count();
        if ($kept + $added < 1) {
            throw ValidationException::withMessages(['new_options' => __('Add at least one photo of the gift.')]);
        }
        if ($kept + $added > self::MAX_OPTIONS) {
            throw ValidationException::withMessages(['new_options' => __('A gift can have up to :n options.', ['n' => self::MAX_OPTIONS])]);
        }

        return [
            'name' => $v['name'],
            'name_ar' => $v['name_ar'] ?? null,
            'description' => $v['description'] ?? null,
            'description_ar' => $v['description_ar'] ?? null,
            'points_required' => (int) $v['points_required'],
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function syncOptions(Request $request, Gift $gift): void
    {
        $order = 0;
        foreach ($gift->options as $option) {
            $row = (array) $request->input("options.{$option->id}", []);
            if (! empty($row['remove'])) {
                $this->images->delete($option->image);
                $option->delete();

                continue;
            }
            $option->update(['label' => $row['label'] ?? null, 'label_ar' => $row['label_ar'] ?? null, 'sort_order' => $order++]);
        }

        foreach ((array) $request->input('new_options', []) as $i => $row) {
            $file = $request->file("new_options.{$i}.image");
            if (! $file) {
                continue;
            }
            try {
                $path = $this->images->store($file, 'gifts', 1200, 480, 84)['path'];
            } catch (\RuntimeException) {
                throw ValidationException::withMessages(["new_options.{$i}.image" => __('We could not read this image. Please upload a JPG, PNG or WebP photo.')]);
            }
            GiftOption::create([
                'gift_id' => $gift->id, 'image' => $path,
                'label' => $row['label'] ?? null, 'label_ar' => $row['label_ar'] ?? null, 'sort_order' => $order++,
            ]);
        }
    }
}
