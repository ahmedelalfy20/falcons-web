{{-- Floating "Back" button on every page except the homepage: previous page on this site, otherwise home. --}}
@unless (request()->routeIs('home'))
    <a href="{{ route('home') }}"
       x-data="{ back() { try { if (document.referrer && new URL(document.referrer).origin === location.origin && history.length > 1) { history.back(); return true } } catch (e) {} return false } }"
       @click="if (back()) $event.preventDefault()"
       class="fixed bottom-5 start-4 z-[90] inline-flex min-h-12 items-center gap-2 rounded-full border border-white/15 bg-ink-900/85 ps-3.5 pe-5 text-sm font-semibold text-fg shadow-lg shadow-black/40 backdrop-blur-md transition hover:-translate-y-0.5 hover:border-brand-400/60 hover:text-brand-200 focus-visible:outline-2 focus-visible:outline-brand-400 sm:bottom-6 sm:start-6"
       aria-label="{{ __('Go back') }}">
        <x-icon name="arrow-left" class="size-5" />
        {{ __('Back') }}
    </a>
@endunless
