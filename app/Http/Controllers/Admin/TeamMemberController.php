<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Services\AuditLogger;
use App\Services\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeamMemberController extends Controller
{
    public function __construct(private ImageOptimizer $images, private AuditLogger $audit) {}

    private function guard(): void
    {
        abort_unless(request()->user()->hasPermission(Permission::ContentManage), 403);
    }

    public function index()
    {
        $this->guard();

        return view('admin.content.team.index', ['members' => TeamMember::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function create()
    {
        $this->guard();

        return view('admin.content.team.form', ['member' => new TeamMember(['is_active' => true, 'sort_order' => (int) TeamMember::max('sort_order') + 1])]);
    }

    public function store(Request $request)
    {
        $this->guard();
        $member = TeamMember::create($this->validated($request));
        $this->handleGallery($request, $member);
        $this->audit->log('content.team_created', $member, ['name' => $member->name]);

        return redirect()->route('admin.team.index')->with('success', __(':name added.', ['name' => $member->name]));
    }

    public function edit(TeamMember $member)
    {
        $this->guard();

        return view('admin.content.team.form', compact('member'));
    }

    public function update(Request $request, TeamMember $member)
    {
        $this->guard();
        $member->update($this->validated($request, $member));
        $this->handleGallery($request, $member);
        $this->audit->log('content.team_updated', $member, ['name' => $member->name]);

        return redirect()->route('admin.team.index')->with('success', __('Profile saved.'));
    }

    public function destroy(TeamMember $member)
    {
        $this->guard();
        $this->images->delete($member->image);
        foreach ($member->gallery ?? [] as $p) {
            $this->images->delete($p);
            $this->images->delete(str_replace('.webp', '-thumb.webp', $p));
        }
        $this->audit->log('content.team_deleted', $member, ['name' => $member->name]);
        $member->delete();

        return redirect()->route('admin.team.index')->with('success', __('Profile removed.'));
    }

    /** Events gallery: remove ticked photos, append new uploads, keep order. */
    private function handleGallery(Request $request, TeamMember $member): void
    {
        $gallery = $member->gallery ?? [];
        $remove = array_intersect((array) $request->input('remove_gallery', []), $gallery);
        foreach ($remove as $p) {
            $this->images->delete($p);
            $this->images->delete(str_replace('.webp', '-thumb.webp', $p));
        }
        $gallery = array_values(array_diff($gallery, $remove));
        foreach ((array) $request->file('gallery', []) as $file) {
            $gallery[] = $this->images->store($file, 'team/events', 1600, 640)['path'];
        }
        $member->update(['gallery' => $gallery]);
    }

    private function validated(Request $request, ?TeamMember $member = null): array
    {
        $v = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'name_ar' => ['nullable', 'string', 'max:120'],
            'slug' => ['nullable', 'alpha_dash', 'max:80', Rule::unique('team_members', 'slug')->ignore($member?->id)],
            'role' => ['nullable', 'string', 'max:120'], 'role_ar' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:80'], 'city_ar' => ['nullable', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:8000'], 'bio_ar' => ['nullable', 'string', 'max:8000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'photo' => ['nullable', 'image', 'max:8192'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'gallery' => ['array', 'max:30'],
            'gallery.*' => ['image', 'max:10240'],
            'remove_gallery' => ['array'],
        ]);
        unset($v['gallery'], $v['remove_gallery']);
        $v['slug'] = ($v['slug'] ?? null) ?: ($member?->slug ?? Str::slug($v['name']));
        $v['is_active'] = $request->boolean('is_active');
        if ($request->hasFile('photo')) {
            $this->images->delete($member?->image);
            $v['image'] = $this->images->store($request->file('photo'), 'team', 1000, null, 82)['path'];
        }
        unset($v['photo']);

        return $v;
    }
}
