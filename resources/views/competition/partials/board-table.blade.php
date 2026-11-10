{{--
    Ranked leaderboard (Alpine). Expects `rows` (top 20) and optional `me` (the
    signed-in leader's own row when outside the top 20) plus `flash` in scope.
--}}
<div class="overflow-hidden rounded-[var(--radius-card)] border border-line bg-ink-850">
    {{-- Column headings --}}
    <div class="grid grid-cols-[4.25rem_1fr_3.75rem] items-center gap-3 border-b border-line bg-ink-900/60 px-4 py-3 text-xs font-semibold uppercase tracking-[0.1em] text-fg-subtle sm:grid-cols-[6.5rem_1fr_7rem_6rem] sm:px-6 lg:grid-cols-[7rem_1fr_11rem_7rem_7rem] rtl:tracking-normal" aria-hidden="true">
        <span>{{ __('Rank') }}</span>
        <span>{{ __('Leader') }}</span>
        <span class="hidden lg:block">{{ __('Team') }}</span>
        <span class="hidden text-end sm:block">{{ __('Approved') }}</span>
        <span class="text-end">{{ __('Points') }}</span>
    </div>

    <template x-if="rows.length === 0">
        <x-empty icon="trophy" :title="__('No leaders yet')" :text="__('Be the first leader on the board.')" />
    </template>

    <ol aria-label="{{ __('Top One') }}">
        <template x-for="row in rows" :key="row.name + row.rank">
            @include('competition.partials.board-row')
        </template>
    </ol>

    <template x-if="me">
        <div>
            <div class="flex items-center gap-3 px-6 py-1.5 text-fg-subtle" aria-hidden="true"><span class="h-px flex-1 border-t border-dashed border-line-strong"></span>•••<span class="h-px flex-1 border-t border-dashed border-line-strong"></span></div>
            <ol aria-label="{{ __('Your position') }}">
                <template x-for="row in [me]" :key="'me'">
                    @include('competition.partials.board-row')
                </template>
            </ol>
        </div>
    </template>
</div>
