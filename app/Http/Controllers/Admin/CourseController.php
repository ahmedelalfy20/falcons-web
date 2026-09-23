<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\AuditLogger;
use App\Services\ImageOptimizer;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    private function guard(): void
    {
        abort_unless(request()->user()->hasPermission(Permission::ContentManage), 403);
    }

    public function index()
    {
        $this->guard();

        return view('admin.content.courses.index', ['courses' => Course::orderBy('track')->orderBy('level')->get()->groupBy('track')]);
    }

    public function edit(Course $course)
    {
        $this->guard();

        return view('admin.content.courses.form', compact('course'));
    }

    public function update(Request $request, Course $course, ImageOptimizer $images, AuditLogger $audit)
    {
        $this->guard();
        $v = $request->validate([
            'title' => ['required', 'string', 'max:120'], 'title_ar' => ['nullable', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:400'], 'subtitle_ar' => ['nullable', 'string', 'max:400'],
            'description' => ['nullable', 'string', 'max:15000'], 'description_ar' => ['nullable', 'string', 'max:15000'],
            'highlights' => ['nullable', 'string', 'max:3000'], 'highlights_ar' => ['nullable', 'string', 'max:3000'],
            'difficulty' => ['nullable', 'in:beginner,intermediate,advanced,professional'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:8192'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);
        $lines = fn ($t) => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $t))));
        $v['highlights'] = $lines($v['highlights'] ?? '');
        $v['highlights_ar'] = $lines($v['highlights_ar'] ?? '');
        $v['is_active'] = $request->boolean('is_active');
        if ($request->boolean('remove_image')) {
            $images->delete($course->image);
            $v['image'] = null;
        }
        if ($request->hasFile('image')) {
            $images->delete($course->image);
            $v['image'] = $images->store($request->file('image'), 'courses', 1400, null)['path'];
        }
        unset($v['remove_image']);
        $course->update($v);
        $audit->log('content.course_updated', $course, ['track' => $course->track, 'level' => $course->level]);

        return redirect()->route('admin.courses.index')->with('success', __('Course saved.'));
    }
}
