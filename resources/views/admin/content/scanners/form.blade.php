@php
    $editing = $scanner->exists;
    $features = old('features', $scanner->features ?: []);
    while (count($features) < 4) { $features[] = ['title' => '', 'title_ar' => '', 'text' => '', 'text_ar' => '']; }
@endphp
<x-layouts.admin :heading="$editing ? __('Edit :name', ['name' => $scanner->name]) : __('Add scanner')">
    <x-slot:breadcrumb><a href="{{ route('admin.scanners.index') }}" class="hover:text-fg">{{ __('Scanners') }}</a> / {{ $editing ? $scanner->name : __('New') }}</x-slot:breadcrumb>

    <form method="POST" action="{{ $editing ? route('admin.scanners.update', $scanner) : route('admin.scanners.store') }}" enctype="multipart/form-data" class="max-w-4xl space-y-5">
        @csrf
        @if ($editing) @method('PUT') @endif

        <section class="card card-pad space-y-5">
            <h2 class="font-bold">{{ __('Basics') }}</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="name" :label="__('Name — English')" :value="$scanner->name" required maxlength="80" />
                <x-field name="name_ar" :label="__('Name — Arabic')" :value="$scanner->name_ar" dir="rtl" maxlength="80" />
                <x-field name="tagline" :label="__('Tagline — English')" :value="$scanner->tagline" maxlength="160" />
                <x-field name="tagline_ar" :label="__('Tagline — Arabic')" :value="$scanner->tagline_ar" dir="rtl" maxlength="160" />
                <x-field name="summary" :label="__('Short summary — English')" :value="$scanner->summary" textarea rows="2" maxlength="500" :hint="__('Shown on the homepage.')" />
                <x-field name="summary_ar" :label="__('Short summary — Arabic')" :value="$scanner->summary_ar" textarea rows="2" dir="rtl" maxlength="500" />
            </div>
            <div class="grid gap-5 sm:grid-cols-4">
                <x-field name="timeframe" :label="__('Timeframe')" :value="$scanner->timeframe" placeholder="H1" dir="ltr" maxlength="60" />
                <x-field name="targets" type="number" :label="__('Targets (TP)')" :value="$scanner->targets" min="1" max="20" />
                <x-field name="methodology" :label="__('Method')" :value="$scanner->methodology" maxlength="60" />
                <div>
                    <label for="accent" class="label">{{ __('Accent colour') }}</label>
                    <div class="flex items-center gap-2" x-data="{ c: @js(old('accent', $scanner->accent)) }">
                        <input type="color" x-model="c" class="h-12 w-14 cursor-pointer rounded-xl border border-line-strong bg-ink-900 p-1" aria-label="{{ __('Pick colour') }}">
                        <input id="accent" name="accent" x-model="c" class="input num" dir="ltr" maxlength="7" required>
                    </div>
                    @error('accent')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-3">
                <x-field name="slug" :label="__('URL slug')" :value="$scanner->slug" dir="ltr" maxlength="60" :hint="__('Leave empty to generate from the name.')" />
                <x-field name="sort_order" type="number" :label="__('Order')" :value="$scanner->sort_order" min="0" max="1000" required />
                <div class="flex items-end"><x-toggle name="is_active" :label="__('Visible on website')" :checked="$scanner->is_active" class="w-full" /></div>
            </div>
        </section>

        <section class="card card-pad space-y-5">
            <h2 class="font-bold">{{ __('Description') }}</h2>
            <p class="-mt-3 text-sm text-fg-muted">{{ __('Separate paragraphs with a blank line. Start a line with • or - for a bullet.') }}</p>
            <div class="grid gap-5 lg:grid-cols-2">
                <x-field name="description" :label="__('English')" :value="$scanner->description" textarea rows="10" maxlength="10000" />
                <x-field name="description_ar" :label="__('Arabic')" :value="$scanner->description_ar" textarea rows="10" dir="rtl" maxlength="10000" />
            </div>
        </section>

        <section class="card card-pad">
            <h2 class="font-bold">{{ __('Key features') }}</h2>
            <p class="mt-1 text-sm text-fg-muted">{{ __('Up to 8. Rows without an English title are ignored.') }}</p>
            <div class="mt-4 space-y-3">
                @foreach ($features as $i => $f)
                    <div class="grid gap-2 rounded-xl border border-line bg-ink-900 p-3 sm:grid-cols-2">
                        <input name="features[{{ $i }}][title]" value="{{ $f['title'] ?? '' }}" placeholder="{{ __('Title — English') }}" class="input !min-h-11" maxlength="80" aria-label="{{ __('Feature :n title (English)', ['n' => $i + 1]) }}">
                        <input name="features[{{ $i }}][title_ar]" value="{{ $f['title_ar'] ?? '' }}" placeholder="{{ __('Title — Arabic') }}" dir="rtl" class="input !min-h-11" maxlength="80" aria-label="{{ __('Feature :n title (Arabic)', ['n' => $i + 1]) }}">
                        <input name="features[{{ $i }}][text]" value="{{ $f['text'] ?? '' }}" placeholder="{{ __('Detail — English') }}" class="input !min-h-11" maxlength="160" aria-label="{{ __('Feature :n detail (English)', ['n' => $i + 1]) }}">
                        <input name="features[{{ $i }}][text_ar]" value="{{ $f['text_ar'] ?? '' }}" placeholder="{{ __('Detail — Arabic') }}" dir="rtl" class="input !min-h-11" maxlength="160" aria-label="{{ __('Feature :n detail (Arabic)', ['n' => $i + 1]) }}">
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card card-pad">
            <h2 class="font-bold">{{ __('Images') }}</h2>
            <p class="mt-1 text-sm text-fg-muted">{{ __('The first image is the cover. Uploads are resized and converted to WebP.') }}</p>
            @if ($scanner->images)
                <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4" role="list">
                    @foreach ($scanner->images as $img)
                        <li>
                            <label class="group relative block cursor-pointer overflow-hidden rounded-xl border border-line bg-white has-[:checked]:border-danger has-[:checked]:opacity-50">
                                <img src="{{ $scanner->thumb($img) }}" alt="" loading="lazy" class="aspect-video w-full object-cover object-top">
                                <span class="absolute inset-x-0 bottom-0 flex items-center gap-2 bg-ink-950/85 px-3 py-2 text-xs"><input type="checkbox" name="remove_images[]" value="{{ $img }}" class="checkbox !size-4">{{ __('Remove') }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            @endif
            <input type="file" name="images[]" accept="image/*" multiple class="mt-4 block w-full text-sm text-fg-muted file:me-3 file:rounded-full file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-fg" aria-label="{{ __('Upload images') }}">
            @error('images.*')<p class="field-error">{{ $message }}</p>@enderror
        </section>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.scanners.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
            <button class="btn btn-primary">{{ $editing ? __('Save scanner') : __('Add scanner') }}</button>
        </div>
    </form>

    @if ($editing)
        <form method="POST" action="{{ route('admin.scanners.destroy', $scanner) }}" class="mt-8 max-w-4xl rounded-[var(--radius-card)] border border-danger/25 p-5" onsubmit="return confirm(@js(__('Delete this scanner and its images?')))">
            @csrf @method('DELETE')
            <p class="font-bold text-danger">{{ __('Delete scanner') }}</p>
            <p class="mt-1 text-sm text-fg-muted">{{ __('To hide it temporarily, switch off “Visible on website” instead.') }}</p>
            <button class="btn btn-danger btn-sm mt-4"><x-icon name="trash" class="size-4" />{{ __('Delete') }}</button>
        </form>
    @endif
</x-layouts.admin>
