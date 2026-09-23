{{-- Toasts: server flashes + client events. aria-live keeps them accessible without stealing focus. --}}
<div x-data="toaster" x-init="
        @if (session('success')) add({ type: 'success', text: @js(session('success')) }); @endif
        @if (session('error')) add({ type: 'error', text: @js(session('error')) }); @endif
     "
     @toast.window="add($event.detail)"
     class="pointer-events-none fixed inset-x-0 bottom-4 z-[100] flex flex-col items-center gap-2 px-4 sm:bottom-6"
     aria-live="polite" role="status">
    <template x-for="t in toasts" :key="t.id">
        <div x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-2" x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="opacity-0"
             class="pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-2xl border px-4 py-3 text-sm shadow-2xl backdrop-blur"
             :class="t.type === 'error' ? 'border-danger/30 bg-[#2a1414]/95 text-red-100' : 'border-brand-500/30 bg-[#0e2219]/95 text-brand-200'">
            <span class="mt-0.5 size-2 shrink-0 rounded-full" :class="t.type === 'error' ? 'bg-danger' : 'bg-brand-400'"></span>
            <p class="flex-1 leading-snug" x-text="t.text"></p>
            <button type="button" class="-m-1 p-1 opacity-70 hover:opacity-100" @click="remove(t.id)" aria-label="{{ __('Dismiss') }}"><x-icon name="x" class="size-4" /></button>
        </div>
    </template>
</div>
