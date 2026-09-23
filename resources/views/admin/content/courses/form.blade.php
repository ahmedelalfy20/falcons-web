<x-layouts.admin :heading="($course->track === 'trading' ? __('Trading') : __('Marketing')).' · '.__('Level :n', ['n' => $course->level])">
    <x-slot:breadcrumb><a href="{{ route('admin.courses.index') }}" class="hover:text-fg">{{ __('Courses') }}</a> / {{ $course->title }}</x-slot:breadcrumb>
    <form method="POST" action="{{ route('admin.courses.update', $course) }}" enctype="multipart/form-data" class="max-w-4xl space-y-5">
        @csrf @method('PUT')
        <section class="card card-pad space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="title" :label="__('Title — English')" :value="$course->title" required maxlength="120" />
                <x-field name="title_ar" :label="__('Title — Arabic')" :value="$course->title_ar" dir="rtl" maxlength="120" />
                <x-field name="subtitle" :label="__('Subtitle — English')" :value="$course->subtitle" textarea rows="2" maxlength="400" />
                <x-field name="subtitle_ar" :label="__('Subtitle — Arabic')" :value="$course->subtitle_ar" textarea rows="2" dir="rtl" maxlength="400" />
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="difficulty" class="label">{{ __('Difficulty') }}</label>
                    <select id="difficulty" name="difficulty" class="input">
                        <option value="">—</option>
                        @foreach (['beginner' => __('Beginner'), 'intermediate' => __('Intermediate'), 'advanced' => __('Advanced'), 'professional' => __('Professional')] as $v => $l)
                            <option value="{{ $v }}" @selected(old('difficulty', $course->difficulty) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end"><x-toggle name="is_active" :label="__('Visible on website')" :checked="$course->is_active" class="w-full" /></div>
            </div>
        </section>
        <section class="card card-pad space-y-5">
            <h2 class="font-bold">{{ __('Highlights') }}</h2>
            <p class="-mt-3 text-sm text-fg-muted">{{ __('3–4 short points, one per line. Shown when the level is expanded.') }}</p>
            <div class="grid gap-5 lg:grid-cols-2">
                <x-field name="highlights" :label="__('English')" :value="implode("\n", $course->highlights ?? [])" textarea rows="5" />
                <x-field name="highlights_ar" :label="__('Arabic')" :value="implode("\n", $course->highlights_ar ?? [])" textarea rows="5" dir="rtl" />
            </div>
        </section>
        <section class="card card-pad space-y-5">
            <h2 class="font-bold">{{ __('Full curriculum') }}</h2>
            <p class="-mt-3 text-sm text-fg-muted">{{ __('Separate paragraphs with a blank line. Start a line with • or - for a bullet.') }}</p>
            <div class="grid gap-5 lg:grid-cols-2">
                <x-field name="description" :label="__('English')" :value="$course->description" textarea rows="12" />
                <x-field name="description_ar" :label="__('Arabic')" :value="$course->description_ar" textarea rows="12" dir="rtl" />
            </div>
        </section>
        <section class="card card-pad">
            <h2 class="font-bold">{{ __('Cover image') }}</h2>
            @if ($course->image)
                <img src="{{ media($course->image) }}" alt="" class="mt-4 aspect-video w-full max-w-sm rounded-xl object-cover">
                <label class="mt-3 flex items-center gap-2 text-sm text-fg-muted"><input type="checkbox" name="remove_image" value="1" class="checkbox">{{ __('Remove image') }}</label>
            @endif
            <input type="file" name="image" accept="image/*" class="mt-4 block w-full text-sm text-fg-muted file:me-3 file:rounded-full file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-fg" aria-label="{{ __('Upload cover image') }}">
        </section>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.courses.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
            <button class="btn btn-primary">{{ __('Save course') }}</button>
        </div>
    </form>
</x-layouts.admin>
