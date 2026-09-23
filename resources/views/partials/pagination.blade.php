@if ($paginator->hasPages())
    <nav class="flex flex-col items-center justify-between gap-3 sm:flex-row" aria-label="{{ __('Page navigation') }}">
        <p class="text-sm text-fg-subtle">
            {!! __('Showing :from–:to of :total', ['from' => '<span class="num text-fg-muted">'.$paginator->firstItem().'</span>', 'to' => '<span class="num text-fg-muted">'.$paginator->lastItem().'</span>', 'total' => '<span class="num text-fg-muted">'.$paginator->total().'</span>']) !!}
        </p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn btn-ghost btn-sm pointer-events-none opacity-40" aria-disabled="true"><x-icon name="chevron-left" class="size-4" /><span class="sr-only">{{ __('Previous') }}</span></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-ghost btn-sm"><x-icon name="chevron-left" class="size-4" /><span class="sr-only">{{ __('Previous') }}</span></a>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-fg-subtle">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="num flex size-9 items-center justify-center rounded-full bg-white/[0.08] text-sm font-semibold">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="num hidden size-9 items-center justify-center rounded-full text-sm text-fg-muted hover:bg-white/[0.05] hover:text-fg sm:flex">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-ghost btn-sm"><x-icon name="chevron-right" class="size-4" /><span class="sr-only">{{ __('Next') }}</span></a>
            @else
                <span class="btn btn-ghost btn-sm pointer-events-none opacity-40" aria-disabled="true"><x-icon name="chevron-right" class="size-4" /><span class="sr-only">{{ __('Next') }}</span></span>
            @endif
        </div>
    </nav>
@endif
