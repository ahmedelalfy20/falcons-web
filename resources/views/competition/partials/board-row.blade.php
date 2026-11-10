<li class="board-row grid grid-cols-[4.25rem_1fr_3.75rem] items-center gap-3 border-b border-line px-4 py-3 transition-colors last:border-0 sm:grid-cols-[6.5rem_1fr_7rem_6rem] sm:px-6 sm:py-3.5 lg:grid-cols-[7rem_1fr_11rem_7rem_7rem]"
    :class="[row.rank <= 3 ? 'rank-' + row.rank : '', row.me ? '!bg-brand-500/[0.09] ring-1 ring-inset ring-brand-500/40' : '', flash[row.name] && Date.now() - flash[row.name] < 2000 ? 'row-flash' : '']">
    <span>
        <template x-if="row.rank <= 3">
            <span class="rank-pill num inline-flex items-center gap-1.5 rounded-full py-1 ps-1 pe-2.5 text-sm font-extrabold sm:gap-2 sm:pe-3">
                <span class="rank-medal flex size-6 items-center justify-center rounded-full"><x-icon name="trophy" class="size-3 text-black/60" /></span>
                <span x-text="'#' + row.rank"></span>
            </span>
        </template>
        <template x-if="row.rank > 3"><span class="num ps-2 text-sm font-bold text-fg-muted" x-text="'#' + row.rank"></span></template>
    </span>
    <span class="flex min-w-0 items-center gap-3">
        <x-avatar-live class="size-8 text-[0.65rem] sm:size-10 sm:text-xs" ::class="row.rank <= 3 ? 'ring-2 ring-[rgb(var(--rk)/0.7)]' : ''" />
        <span class="min-w-0">
            <span class="flex items-center gap-2">
                <span class="truncate font-semibold" :class="row.rank <= 3 ? 'font-bold' : ''" x-text="row.name"></span>
                <span x-show="row.me" class="shrink-0 rounded-full bg-brand-500 px-2 py-0.5 text-[0.65rem] font-bold text-ink-950">{{ __('You') }}</span>
            </span>
            <span class="block truncate text-xs text-fg-subtle lg:hidden" x-show="row.team" x-text="row.team"></span>
        </span>
    </span>
    <span class="hidden truncate text-sm text-fg-muted lg:block" x-text="row.team || '—'"></span>
    <span class="num hidden text-end text-sm text-fg-muted sm:block" x-text="row.accepted"></span>
    <span class="num text-end"><span class="text-lg font-extrabold text-brand-300" x-text="row.score.toLocaleString('en-US')"></span></span>
</li>
