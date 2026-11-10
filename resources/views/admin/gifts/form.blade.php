@php
    $editing = $gift->exists;
    $existing = $editing ? $gift->options : collect();
@endphp
<x-layouts.admin :heading="$editing ? __('Edit :name', ['name' => $gift->name]) : __('Add gift')">
    <x-slot:breadcrumb><a href="{{ route('admin.gifts.index') }}" class="hover:text-fg">{{ __('Gifts') }}</a> / {{ $editing ? $gift->name : __('New') }}</x-slot:breadcrumb>

    <form method="POST" action="{{ $editing ? route('admin.gifts.update', $gift) : route('admin.gifts.store') }}" enctype="multipart/form-data" class="max-w-3xl space-y-5"
          x-data="{ kept: {{ $existing->count() }}, rows: [{ id: 1, preview: null }], max: 4,
                    get total() { return this.kept + this.rows.filter(r => r.preview).length },
                    add() { if (this.kept + this.rows.length < this.max) this.rows.push({ id: Date.now(), preview: null }) } }">
        @csrf
        @if ($editing) @method('PUT') @endif

        <section class="card card-pad space-y-5">
            <h2 class="font-bold">{{ __('Gift details') }}</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="name" :label="__('Name — English')" :value="$gift->name" required maxlength="120" />
                <x-field name="name_ar" :label="__('Name — Arabic')" :value="$gift->name_ar" dir="rtl" maxlength="120" />
                <x-field name="description" :label="__('Short description — English')" :value="$gift->description" textarea rows="2" maxlength="500" />
                <x-field name="description_ar" :label="__('Short description — Arabic')" :value="$gift->description_ar" dir="rtl" textarea rows="2" maxlength="500" />
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="points_required" type="number" :label="__('Points needed to unlock')" :value="$gift->points_required" min="1" required :hint="__('Total points, e.g. 30 for the first gift and 60 for the next.')" />
                <div class="flex items-end"><x-toggle name="is_active" :label="__('Visible to leaders')" :checked="$gift->is_active" class="w-full" /></div>
            </div>
        </section>

        <section class="card card-pad space-y-4">
            <div>
                <h2 class="font-bold">{{ __('Gift photos') }}</h2>
                <p class="mt-1 text-sm text-fg-muted">{{ __('One photo = a single gift. Two or more photos = the leader chooses one of them.') }}</p>
            </div>
            @error('new_options')<x-alert level="danger">{{ $message }}</x-alert>@enderror

            <div class="grid gap-3 sm:grid-cols-2">
                {{-- Existing options --}}
                @foreach ($existing as $o)
                    <div class="rounded-2xl border border-line p-3" x-data="{ remove: @js((bool) old("options.{$o->id}.remove")) }" :class="remove && 'opacity-50'">
                        <div class="relative aspect-[4/3] overflow-hidden rounded-xl bg-white">
                            <img src="{{ $o->imageUrl(true) }}" alt="" class="h-full w-full object-contain">
                        </div>
                        <div class="mt-3 grid gap-2">
                            <input name="options[{{ $o->id }}][label]" value="{{ old("options.{$o->id}.label", $o->label) }}" class="input" placeholder="{{ __('Option name — English (optional)') }}" maxlength="120" aria-label="{{ __('Option name — English (optional)') }}">
                            <input name="options[{{ $o->id }}][label_ar]" value="{{ old("options.{$o->id}.label_ar", $o->label_ar) }}" class="input" dir="rtl" placeholder="{{ __('Option name — Arabic (optional)') }}" maxlength="120" aria-label="{{ __('Option name — Arabic (optional)') }}">
                        </div>
                        <label class="mt-3 inline-flex cursor-pointer items-center gap-2 text-sm text-danger">
                            <input type="checkbox" name="options[{{ $o->id }}][remove]" value="1" x-model="remove" @change="kept += remove ? -1 : 1" class="accent-current"> {{ __('Remove this photo') }}
                        </label>
                    </div>
                @endforeach

                {{-- New options --}}
                <template x-for="(row, i) in rows" :key="row.id">
                    <div class="rounded-2xl border border-dashed border-line-strong p-3">
                        <label class="relative flex aspect-[4/3] cursor-pointer items-center justify-center overflow-hidden rounded-xl bg-ink-900 text-center">
                            <template x-if="row.preview"><img :src="row.preview" alt="" class="h-full w-full bg-white object-contain"></template>
                            <span x-show="!row.preview" class="flex flex-col items-center gap-2 text-sm text-fg-muted"><x-icon name="image" class="size-7" />{{ __('Choose a photo') }}</span>
                            <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp" :name="`new_options[${i}][image]`"
                                   @change="const f = $event.target.files[0]; row.preview = f ? URL.createObjectURL(f) : null">
                        </label>
                        <div class="mt-3 grid gap-2">
                            <input :name="`new_options[${i}][label]`" class="input" placeholder="{{ __('Option name — English (optional)') }}" maxlength="120" aria-label="{{ __('Option name — English (optional)') }}">
                            <input :name="`new_options[${i}][label_ar]`" class="input" dir="rtl" placeholder="{{ __('Option name — Arabic (optional)') }}" maxlength="120" aria-label="{{ __('Option name — Arabic (optional)') }}">
                        </div>
                        <button type="button" class="mt-3 text-sm text-fg-muted hover:text-danger" x-show="rows.length > 1" @click="rows.splice(i, 1)">{{ __('Remove') }}</button>
                    </div>
                </template>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <button type="button" class="btn btn-outline btn-sm" @click="add()" :disabled="kept + rows.length >= max"><x-icon name="plus" class="size-4" />{{ __('Add another option') }}</button>
                <p class="text-xs text-fg-subtle">{{ __('Up to 4 photos. JPG, PNG or WebP — resized automatically.') }}</p>
            </div>
            @foreach ($errors->get('new_options.*') as $messages)
                <p class="field-error">{{ $messages[0] }}</p>
            @endforeach
        </section>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.gifts.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
            <button class="btn btn-primary">{{ $editing ? __('Save gift') : __('Add gift') }}</button>
        </div>
    </form>

    @if ($editing)
        <form method="POST" action="{{ route('admin.gifts.destroy', $gift) }}" class="mt-8 max-w-3xl rounded-[var(--radius-card)] border border-danger/25 p-5" onsubmit="return confirm(@js(__('Delete this gift? Leaders’ requests for it are removed too.')))">
            @csrf @method('DELETE')
            <p class="font-bold text-danger">{{ __('Delete gift') }}</p>
            <button class="btn btn-danger btn-sm mt-3"><x-icon name="trash" class="size-4" />{{ __('Delete') }}</button>
        </form>
    @endif
</x-layouts.admin>
