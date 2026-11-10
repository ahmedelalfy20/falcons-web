<x-layouts.admin :heading="__('Gifts')" :subheading="__('Gift ladder for leaders. Points are never spent: each gift unlocks when a leader’s total points reach its target.')">
    <x-slot:actions><a href="{{ route('admin.gifts.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" />{{ __('Add gift') }}</a></x-slot:actions>

    {{-- Ladder --}}
    @if ($gifts->isEmpty())
        <div class="card">
            <x-empty icon="gift" :title="__('No gifts yet')" :text="__('Add the first gift with its photo and the points needed to unlock it.')">
                <a href="{{ route('admin.gifts.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" />{{ __('Add gift') }}</a>
            </x-empty>
        </div>
    @else
        <ol class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" role="list">
            @foreach ($gifts as $gift)
                <li class="card overflow-hidden">
                    <div @class(['grid aspect-[16/10] grid-rows-1 gap-px overflow-hidden bg-line', 'grid-cols-2' => $gift->options->count() > 1])>
                        @forelse ($gift->options->take(4) as $o)
                            <img src="{{ $o->imageUrl(true) }}" alt="" loading="lazy" class="h-full w-full bg-white object-contain">
                        @empty
                            <span class="flex items-center justify-center bg-ink-900"><x-icon name="image" class="size-8 text-fg-subtle" /></span>
                        @endforelse
                    </div>
                    <div class="flex items-start gap-3 p-4">
                        <span class="num flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-500/10 text-sm font-bold text-brand-300">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-bold">{{ $gift->tr('name') }}</p>
                            <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-fg-subtle">
                                <span class="num font-semibold text-brand-300">{{ number_format($gift->points_required) }} {{ __('points') }}</span>
                                @if ($gift->options->count() > 1)<span>· {{ __('Choice of :n', ['n' => $gift->options->count()]) }}</span>@endif
                                @if ($gift->claims_count)<span>· {{ trans_choice('{1} 1 claim|[2,*] :count claims', $gift->claims_count) }}</span>@endif
                                @unless ($gift->is_active)<span class="badge badge-neutral">{{ __('Hidden') }}</span>@endunless
                            </p>
                        </div>
                        <a href="{{ route('admin.gifts.edit', $gift) }}" class="btn btn-outline btn-sm shrink-0"><x-icon name="edit" class="size-4" />{{ __('Edit') }}</a>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif

    {{-- Claims --}}
    <section class="card mt-8" aria-labelledby="claims-title">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
            <h2 id="claims-title" class="font-bold">{{ __('Gift requests') }}</h2>
            <nav class="flex gap-1 rounded-full border border-line p-1 text-sm" aria-label="{{ __('Filter') }}">
                @foreach (['requested' => __('To deliver').($requestedCount ? " ({$requestedCount})" : ''), 'delivered' => __('Delivered'), 'all' => __('All')] as $key => $label)
                    <a href="{{ route('admin.gifts.index', ['status' => $key]) }}" @class(['rounded-full px-3 py-1.5', 'bg-ink-800 font-semibold text-fg' => $status === $key, 'text-fg-muted hover:text-fg' => $status !== $key])>{{ $label }}</a>
                @endforeach
            </nav>
        </div>
        @forelse ($claims as $claim)
            <div class="flex flex-wrap items-center gap-4 border-b border-line px-5 py-3.5 last:border-0">
                @if ($claim->option)
                    <img src="{{ $claim->option->imageUrl(true) }}" alt="" class="size-14 shrink-0 rounded-xl bg-white object-contain">
                @else
                    <span class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-ink-900"><x-icon name="gift" class="size-5 text-fg-subtle" /></span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold">
                        @if ($claim->leader)<a href="{{ route('admin.leaders.show', $claim->leader) }}" class="hover:text-brand-200">{{ $claim->leader->name }}</a>@else — @endif
                    </p>
                    <p class="truncate text-sm text-fg-muted">{{ $claim->gift?->tr('name') }}@if ($claim->option && ($claim->option->tr('label'))) · <span class="text-fg">{{ $claim->option->tr('label') }}</span>@endif</p>
                    <p class="text-xs text-fg-subtle">@if ($claim->leader)<span dir="ltr">{{ $claim->leader->phone }}</span> · @endif{{ $claim->updated_at->diffForHumans() }}</p>
                </div>
                @if ($claim->isDelivered())
                    <span class="badge badge-accepted">{{ __('Delivered') }}</span>
                    <form method="POST" action="{{ route('admin.gifts.deliver', $claim) }}">@csrf<input type="hidden" name="undo" value="1"><button class="btn btn-ghost btn-sm">{{ __('Undo') }}</button></form>
                @else
                    <form method="POST" action="{{ route('admin.gifts.deliver', $claim) }}">@csrf<button class="btn btn-primary btn-sm"><x-icon name="check" class="size-4" />{{ __('Mark delivered') }}</button></form>
                @endif
            </div>
        @empty
            <x-empty icon="inbox" :title="__('No gift requests here')" :text="__('When a leader unlocks a gift and picks it, the request shows up here.')" />
        @endforelse
    </section>
</x-layouts.admin>
