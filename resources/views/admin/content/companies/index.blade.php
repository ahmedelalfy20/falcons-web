@php
    $enabled = $section['enabled'] ?? true;
    $speed = $section['speed'] ?? 'normal';
@endphp
<x-layouts.admin :heading="__('Companies')" :subheading="__('Companies under our management, shown as a moving logo strip on the homepage.')">
    <x-slot:actions><a href="{{ route('admin.companies.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" />{{ __('Add company') }}</a></x-slot:actions>

    <div class="grid gap-5 xl:grid-cols-12">
        <div class="xl:col-span-8">
            @if ($companies->isEmpty())
                <div class="card">
                    <x-empty icon="building" :title="__('No companies yet')" :text="__('Add the first company — the section appears on the homepage as soon as one is visible.')">
                        <a href="{{ route('admin.companies.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" />{{ __('Add company') }}</a>
                    </x-empty>
                </div>
            @else
                <ul class="grid gap-3 sm:grid-cols-2" role="list">
                    @foreach ($companies as $c)
                        <li class="card flex items-center gap-4 p-3 pe-4">
                            <span @class(['flex h-16 w-28 shrink-0 items-center justify-center rounded-xl p-2', 'bg-white' => $c->on_light, 'bg-ink-900' => ! $c->on_light])>
                                <img src="{{ media($c->logo) }}" alt="" loading="lazy" class="max-h-full max-w-full object-contain">
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold">{{ $c->name }}</p>
                                @if ($c->name_ar)<p class="truncate text-sm text-fg-muted" dir="rtl">{{ $c->name_ar }}</p>@endif
                                <p class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-fg-subtle">
                                    <span class="num">#{{ $c->sort_order }}</span>
                                    @unless ($c->is_active)<span class="badge badge-neutral">{{ __('Hidden') }}</span>@endunless
                                    @if ($c->url)<x-icon name="external" class="size-3.5" />@endif
                                </p>
                            </div>
                            <a href="{{ route('admin.companies.edit', $c) }}" class="btn btn-outline btn-sm shrink-0"><x-icon name="edit" class="size-4" />{{ __('Edit') }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Section settings --}}
        <form method="POST" action="{{ route('admin.companies.section') }}" class="card card-pad space-y-4 self-start xl:col-span-4">
            @csrf @method('PUT')
            <h2 class="font-bold">{{ __('Section settings') }}</h2>
            <x-toggle name="enabled" :label="__('Show the section on the homepage')" :checked="$enabled" />
            <x-field name="title" :label="__('Title — English')" :value="$section['title'] ?? ''" :placeholder="__('Companies Under Our Management')" maxlength="80" />
            <x-field name="title_ar" :label="__('Title — Arabic')" :value="$section['title_ar'] ?? ''" placeholder="شركات تحت إدارتنا" dir="rtl" maxlength="80" />
            <x-field name="subtitle" :label="__('Subtitle — English')" :value="$section['subtitle'] ?? ''" maxlength="200" textarea rows="2" />
            <x-field name="subtitle_ar" :label="__('Subtitle — Arabic')" :value="$section['subtitle_ar'] ?? ''" dir="rtl" maxlength="200" textarea rows="2" />
            <div>
                <span class="label">{{ __('Scrolling speed') }}</span>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['slow' => __('Slow'), 'normal' => __('Normal'), 'fast' => __('Fast')] as $val => $label)
                        <label class="flex min-h-11 cursor-pointer items-center justify-center rounded-xl border border-line bg-ink-900 text-sm has-[:checked]:border-brand-500/60 has-[:checked]:bg-brand-500/[0.08]">
                            <input type="radio" name="speed" value="{{ $val }}" @checked(old('speed', $speed) === $val) class="sr-only"> {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
            <p class="hint">{{ __('Leave the titles empty to use the default text.') }}</p>
            <button class="btn btn-primary w-full">{{ __('Save section') }}</button>
        </form>
    </div>
</x-layouts.admin>
