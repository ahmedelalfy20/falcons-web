@php $editing = $member->exists; @endphp
<x-layouts.admin :heading="$editing ? __('Edit :name', ['name' => $member->name]) : __('Add profile')">
    <x-slot:breadcrumb><a href="{{ route('admin.team.index') }}" class="hover:text-fg">{{ __('Team profiles') }}</a> / {{ $editing ? $member->name : __('New') }}</x-slot:breadcrumb>

    <form method="POST" action="{{ $editing ? route('admin.team.update', $member) : route('admin.team.store') }}" enctype="multipart/form-data" class="max-w-4xl space-y-5">
        @csrf
        @if ($editing) @method('PUT') @endif
        <section class="card card-pad space-y-5">
            <div class="flex items-center gap-4">
                <img src="{{ media($member->image) ?: asset('media/brand/logo.webp') }}" alt="" class="size-24 rounded-2xl bg-[#ecebe8] object-cover object-top">
                <div class="flex-1">
                    <label for="photo" class="label">{{ __('Portrait photo') }}</label>
                    <input id="photo" type="file" name="photo" accept="image/*" class="block w-full text-sm text-fg-muted file:me-3 file:rounded-full file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-fg">
                    <p class="hint">{{ __('Vertical 4:5 works best. Converted to WebP automatically.') }}</p>
                    @error('photo')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="name" :label="__('Name — English')" :value="$member->name" required maxlength="120" />
                <x-field name="name_ar" :label="__('Name — Arabic')" :value="$member->name_ar" dir="rtl" maxlength="120" />
                <x-field name="role" :label="__('Position — English')" :value="$member->role" maxlength="120" />
                <x-field name="role_ar" :label="__('Position — Arabic')" :value="$member->role_ar" dir="rtl" maxlength="120" />
                <x-field name="city" :label="__('City — English')" :value="$member->city" maxlength="80" />
                <x-field name="city_ar" :label="__('City — Arabic')" :value="$member->city_ar" dir="rtl" maxlength="80" />
                <x-field name="experience_years" type="number" :label="__('Years of experience')" :value="$member->experience_years" min="0" max="60" :hint="__('Optional — shown on the profile page.')" />
                <div>
                    <label for="company_id" class="label">{{ __('Company') }}</label>
                    <select id="company_id" name="company_id" class="input">
                        <option value="">{{ __('None') }}</option>
                        @foreach (\App\Models\Company::orderBy('sort_order')->get() as $c)
                            <option value="{{ $c->id }}" @selected((int) old('company_id', $member->company_id) === $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <p class="hint">{{ __('Optional — the company logo appears on the profile page.') }}</p>
                </div>
            </div>
        </section>
        <section class="card card-pad space-y-5">
            <h2 class="font-bold">{{ __('Biography') }}</h2>
            <p class="-mt-3 text-sm text-fg-muted">{{ __('Shown on the profile page. Until it is filled in, visitors see “Biography coming soon”.') }}</p>
            <div class="grid gap-5 lg:grid-cols-2">
                <x-field name="bio" :label="__('English')" :value="$member->bio" textarea rows="10" maxlength="8000" />
                <x-field name="bio_ar" :label="__('Arabic')" :value="$member->bio_ar" textarea rows="10" dir="rtl" maxlength="8000" />
            </div>
        </section>
        <section class="card card-pad space-y-4" x-data="{ n: 0 }">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold">{{ __('Events gallery') }} <span class="num ms-1 text-sm font-normal text-fg-subtle">{{ count($member->gallery ?? []) }}</span></h2>
                    <p class="text-sm text-fg-muted">{{ __('Photos from events and workshops, shown on the profile page.') }}</p>
                </div>
                <label class="btn btn-outline btn-sm cursor-pointer"><x-icon name="plus" class="size-4" /><span x-text="n ? @js(__('Selected:')) + ' ' + n : @js(__('Add photos'))">{{ __('Add photos') }}</span>
                    <input type="file" name="gallery[]" accept="image/*" multiple class="sr-only" @change="n = $event.target.files.length"></label>
            </div>
            @if ($member->gallery)
                <ul class="grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-7" role="list">
                    @foreach ($member->gallery as $p)
                        <li class="relative">
                            <label class="group block cursor-pointer overflow-hidden rounded-xl bg-ink-800 has-[:checked]:ring-2 has-[:checked]:ring-danger">
                                <img src="{{ media(file_exists(public_path(str_replace('.webp', '-thumb.webp', $p))) ? str_replace('.webp', '-thumb.webp', $p) : $p) }}" alt="" loading="lazy" class="aspect-square w-full object-cover transition-opacity group-has-[:checked]:opacity-40">
                                <input type="checkbox" name="remove_gallery[]" value="{{ $p }}" class="peer sr-only">
                                <span class="absolute end-1.5 top-1.5 flex size-8 items-center justify-center rounded-full bg-ink-950/80 text-fg backdrop-blur peer-checked:bg-danger" title="{{ __('Remove') }}"><x-icon name="trash" class="size-4" /></span>
                            </label>
                        </li>
                    @endforeach
                </ul>
                <p class="hint">{{ __('Tap the photos you want to remove, then save.') }}</p>
            @endif
            @error('gallery.*')<p class="field-error">{{ $message }}</p>@enderror
        </section>
        <section class="card card-pad grid gap-5 sm:grid-cols-3">
            <x-field name="slug" :label="__('URL slug')" :value="$member->slug" dir="ltr" maxlength="80" :hint="__('Leave empty to generate from the name.')" />
            <x-field name="sort_order" type="number" :label="__('Order')" :value="$member->sort_order" min="0" max="1000" required />
            <div class="flex items-end"><x-toggle name="is_active" :label="__('Visible on website')" :checked="$member->is_active" class="w-full" /></div>
        </section>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.team.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
            <button class="btn btn-primary">{{ $editing ? __('Save profile') : __('Add profile') }}</button>
        </div>
    </form>
    @if ($editing)
        <form method="POST" action="{{ route('admin.team.destroy', $member) }}" class="mt-8 max-w-4xl rounded-[var(--radius-card)] border border-danger/25 p-5" onsubmit="return confirm(@js(__('Delete this profile?')))">
            @csrf @method('DELETE')
            <p class="font-bold text-danger">{{ __('Delete profile') }}</p>
            <button class="btn btn-danger btn-sm mt-3"><x-icon name="trash" class="size-4" />{{ __('Delete') }}</button>
        </form>
    @endif
</x-layouts.admin>
