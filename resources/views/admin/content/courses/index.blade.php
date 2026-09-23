<x-layouts.admin :heading="__('Courses')" :subheading="__('Two tracks with four levels each. Levels without details show “coming soon”.')">
    <div class="grid gap-5 lg:grid-cols-2">
        @foreach ($courses as $track => $items)
            <section class="card" aria-labelledby="track-{{ $track }}">
                <h2 id="track-{{ $track }}" class="border-b border-line px-5 py-4 font-bold">{{ $track === 'trading' ? __('Trading track') : __('Marketing track') }}</h2>
                @foreach ($items as $c)
                    <a href="{{ route('admin.courses.edit', $c) }}" class="flex items-center gap-4 border-b border-line px-5 py-4 last:border-0 hover:bg-white/[0.02]">
                        <span class="num text-2xl font-extrabold text-fg-subtle">{{ str_pad($c->level, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold">{{ $c->title }}</span>
                            <span class="block truncate text-sm text-fg-muted">{{ $c->subtitle ?: __('No subtitle yet') }}</span>
                        </span>
                        @if ($c->hasDetails())<span class="badge badge-accepted">{{ __('Complete') }}</span>@else<span class="badge badge-pending">{{ __('Needs details') }}</span>@endif
                        @unless ($c->is_active)<span class="badge badge-neutral">{{ __('Hidden') }}</span>@endunless
                        <x-icon name="chevron-right" class="size-4 text-fg-subtle" />
                    </a>
                @endforeach
            </section>
        @endforeach
    </div>
</x-layouts.admin>
