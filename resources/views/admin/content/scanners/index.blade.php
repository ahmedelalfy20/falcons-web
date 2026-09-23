<x-layouts.admin :heading="__('Scanners')" :subheading="__('Scanners appear on the homepage in this order. Adding one needs no code changes.')">
    <x-slot:actions><a href="{{ route('admin.scanners.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" />{{ __('Add scanner') }}</a></x-slot:actions>
    @if ($scanners->isEmpty())
        <div class="card"><x-empty icon="scan" :title="__('No scanners yet')"><a href="{{ route('admin.scanners.create') }}" class="btn btn-primary">{{ __('Add scanner') }}</a></x-empty></div>
    @else
        <ul class="grid gap-3 md:grid-cols-2" role="list">
            @foreach ($scanners as $s)
                <li class="card flex items-center gap-4 p-3 pe-4">
                    <div class="relative h-20 w-32 shrink-0 overflow-hidden rounded-xl bg-white">
                        @if ($s->coverImage())<img src="{{ $s->thumb($s->coverImage()) }}" alt="" loading="lazy" class="h-full w-full object-cover object-top">@endif
                        <span class="absolute inset-x-0 top-0 h-1" style="background: {{ $s->accent }}"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="flex items-center gap-2 font-bold"><span class="num text-xs font-medium text-fg-subtle">{{ $s->sort_order }}</span>{{ $s->name }} @unless ($s->is_active)<span class="badge badge-neutral">{{ __('Hidden') }}</span>@endunless</p>
                        <p class="truncate text-sm text-fg-muted">{{ $s->tagline }}</p>
                        <p class="mt-1 text-xs text-fg-subtle">{{ trans_choice(':count image|:count images', count($s->images ?? [])) }}</p>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <a href="{{ route('scanners.show', $s) }}" target="_blank" class="btn btn-ghost btn-sm !px-2.5" aria-label="{{ __('View on website') }}"><x-icon name="external" class="size-4" /></a>
                        <a href="{{ route('admin.scanners.edit', $s) }}" class="btn btn-outline btn-sm"><x-icon name="edit" class="size-4" />{{ __('Edit') }}</a>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.admin>
