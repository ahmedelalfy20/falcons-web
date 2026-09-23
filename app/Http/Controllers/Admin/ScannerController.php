<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Scanner;
use App\Services\AuditLogger;
use App\Services\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Scanners are fully data-driven: adding a new scanner is a form submission,
 * no template changes required.
 */
class ScannerController extends Controller
{
    public function __construct(private ImageOptimizer $images, private AuditLogger $audit) {}

    private function guard(): void
    {
        abort_unless(request()->user()->hasPermission(Permission::ContentManage), 403);
    }

    public function index()
    {
        $this->guard();

        return view('admin.content.scanners.index', ['scanners' => Scanner::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function create()
    {
        $this->guard();

        return view('admin.content.scanners.form', ['scanner' => new Scanner(['accent' => '#4ADE9A', 'is_active' => true, 'sort_order' => (int) Scanner::max('sort_order') + 1, 'features' => []])]);
    }

    public function store(Request $request)
    {
        $this->guard();
        $scanner = Scanner::create($this->validated($request));
        $this->handleImages($request, $scanner);
        $this->audit->log('content.scanner_created', $scanner, ['name' => $scanner->name]);

        return redirect()->route('admin.scanners.index')->with('success', __('Scanner ":name" added.', ['name' => $scanner->name]));
    }

    public function edit(Scanner $scanner)
    {
        $this->guard();

        return view('admin.content.scanners.form', compact('scanner'));
    }

    public function update(Request $request, Scanner $scanner)
    {
        $this->guard();
        $scanner->update($this->validated($request, $scanner));
        $this->handleImages($request, $scanner);
        $this->audit->log('content.scanner_updated', $scanner, ['name' => $scanner->name]);

        return redirect()->route('admin.scanners.index')->with('success', __('Scanner saved.'));
    }

    public function destroy(Scanner $scanner)
    {
        $this->guard();
        foreach ($scanner->images ?? [] as $img) {
            $this->images->delete($img);
            $this->images->delete(str_replace('.webp', '-thumb.webp', $img));
        }
        $this->audit->log('content.scanner_deleted', $scanner, ['name' => $scanner->name]);
        $scanner->delete();

        return redirect()->route('admin.scanners.index')->with('success', __('Scanner removed.'));
    }

    private function handleImages(Request $request, Scanner $scanner): void
    {
        $images = $scanner->images ?? [];
        $remove = (array) $request->input('remove_images', []);
        foreach ($remove as $path) {
            if (in_array($path, $images, true)) {
                $this->images->delete($path);
                $this->images->delete(str_replace('.webp', '-thumb.webp', $path));
            }
        }
        $images = array_values(array_diff($images, $remove));
        foreach ((array) $request->file('images', []) as $file) {
            $images[] = $this->images->store($file, 'scanners', 1600, 640)['path'];
        }
        $scanner->update(['images' => $images]);
    }

    private function validated(Request $request, ?Scanner $scanner = null): array
    {
        $v = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'name_ar' => ['nullable', 'string', 'max:80'],
            'slug' => ['nullable', 'alpha_dash', 'max:60', Rule::unique('scanners', 'slug')->ignore($scanner?->id)],
            'tagline' => ['nullable', 'string', 'max:160'], 'tagline_ar' => ['nullable', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:500'], 'summary_ar' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'], 'description_ar' => ['nullable', 'string', 'max:10000'],
            'timeframe' => ['nullable', 'string', 'max:60'],
            'methodology' => ['nullable', 'string', 'max:60'],
            'targets' => ['nullable', 'integer', 'min:1', 'max:20'],
            'accent' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'features' => ['array', 'max:8'],
            'features.*.title' => ['nullable', 'string', 'max:80'],
            'features.*.title_ar' => ['nullable', 'string', 'max:80'],
            'features.*.text' => ['nullable', 'string', 'max:160'],
            'features.*.text_ar' => ['nullable', 'string', 'max:160'],
            'images' => ['array', 'max:12'],
            'images.*' => ['image', 'max:8192'],
            'remove_images' => ['array'],
        ]);
        $v['slug'] = $v['slug'] ?: Str::slug($v['name']);
        $v['is_active'] = $request->boolean('is_active');
        $v['features'] = array_values(array_filter($v['features'] ?? [], fn ($f) => filled($f['title'] ?? null)));
        unset($v['images'], $v['remove_images']);

        return $v;
    }
}
