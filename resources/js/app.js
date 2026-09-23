import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

document.documentElement.classList.add('js');

/* ------------------------------------------------------------------
 | Scroll reveal — one IntersectionObserver for the whole page.
 * ------------------------------------------------------------------ */
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
function initReveal() {
    const items = document.querySelectorAll('[data-reveal]:not(.is-in)');
    if (reduceMotion || !('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-in'));
        return;
    }
    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-in');
                    io.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.08 },
    );
    items.forEach((el) => io.observe(el));
}

/* ------------------------------------------------------------------
 | Server-authoritative countdown.
 | The server reports `remaining_seconds`; we count down with
 | performance.now() (monotonic — unaffected by device clock changes or
 | page refreshes) and resync on every poll.
 * ------------------------------------------------------------------ */
function formatClock(total) {
    total = Math.max(0, Math.floor(total));
    const d = Math.floor(total / 86400);
    const h = Math.floor((total % 86400) / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    const pad = (n) => String(n).padStart(2, '0');
    return { d, h: pad(h), m: pad(m), s: pad(s), text: (d ? `${d}d ` : '') + `${pad(h)}:${pad(m)}:${pad(s)}` };
}

Alpine.data('countdown', (initial = {}) => ({
    remaining: initial.remaining_seconds ?? 0,
    status: initial.status ?? 'draft',
    anchor: performance.now(),
    base: initial.remaining_seconds ?? 0,
    frame: null,
    clock: formatClock(initial.remaining_seconds ?? 0),
    init() {
        this.tick();
        this.$watch('status', () => this.tick());
        window.addEventListener('round:update', (e) => this.sync(e.detail));
    },
    sync(round) {
        if (!round) return;
        this.status = round.status;
        this.base = round.remaining_seconds;
        this.anchor = performance.now();
        this.remaining = this.base;
        this.clock = formatClock(this.remaining);
    },
    tick() {
        cancelAnimationFrame(this.frame);
        const loop = () => {
            if (this.status === 'running') {
                const elapsed = (performance.now() - this.anchor) / 1000;
                const next = Math.max(0, this.base - elapsed);
                if (Math.floor(next) !== Math.floor(this.remaining)) {
                    this.remaining = next;
                    this.clock = formatClock(Math.ceil(next));
                    if (next <= 0) window.dispatchEvent(new CustomEvent('round:expired'));
                }
            }
            this.frame = setTimeout(loop, 250);
        };
        loop();
    },
    destroy() {
        clearTimeout(this.frame);
    },
}));

/* ------------------------------------------------------------------
 | Poller — fetches a JSON endpoint on an interval, pauses when the tab
 | is hidden, passes the last version so unchanged responses stay tiny.
 * ------------------------------------------------------------------ */
Alpine.data('poller', (url, interval = 5000, initialVersion = null) => ({
    version: initialVersion,
    timer: null,
    busy: false,
    init() {
        this.schedule(0);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) this.schedule(0);
        });
        window.addEventListener('round:expired', () => this.schedule(1200));
        window.addEventListener('live:refresh', () => this.schedule(0));
    },
    schedule(delay) {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.fetch(), delay ?? interval);
    },
    async fetch() {
        if (document.hidden || this.busy) return this.schedule(interval);
        this.busy = true;
        try {
            const sep = url.includes('?') ? '&' : '?';
            const res = await fetch(`${url}${sep}v=${this.version ?? ''}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (res.ok) {
                const data = await res.json();
                if (data.round) window.dispatchEvent(new CustomEvent('round:update', { detail: data.round }));
                if (!data.unchanged) {
                    this.version = data.v ?? this.version;
                    this.$dispatch('live-data', data);
                }
            }
        } catch (_) {
            /* network hiccup: try again next tick */
        } finally {
            this.busy = false;
            this.schedule(interval);
        }
    },
}));

/* ------------------------------------------------------------------
 | Lightweight lightbox (images load only when opened).
 * ------------------------------------------------------------------ */
Alpine.data('lightbox', (images = []) => ({
    images,
    index: 0,
    open: false,
    loaded: false,
    show(i) {
        this.index = i;
        this.loaded = false;
        this.open = true;
        document.documentElement.style.overflow = 'hidden';
        this.$nextTick(() => {
            this.$refs.close?.focus();
            this.checkCached();
        });
    },
    // A cached image may finish before Alpine re-binds; don't leave the spinner up.
    checkCached() {
        const img = this.$refs.img;
        if (img && img.complete && img.naturalWidth > 0) this.loaded = true;
    },
    close() {
        this.open = false;
        document.documentElement.style.overflow = '';
    },
    next() {
        this.loaded = false;
        this.index = (this.index + 1) % this.images.length;
        this.$nextTick(() => this.checkCached());
    },
    prev() {
        this.loaded = false;
        this.index = (this.index - 1 + this.images.length) % this.images.length;
        this.$nextTick(() => this.checkCached());
    },
    get current() {
        return this.images[this.index] || {};
    },
    touchX: null,
    touchStart(e) {
        this.touchX = e.changedTouches[0].clientX;
    },
    touchEnd(e) {
        if (this.touchX === null) return;
        const dx = e.changedTouches[0].clientX - this.touchX;
        if (Math.abs(dx) > 50) (dx < 0) !== (document.dir === 'rtl') ? this.next() : this.prev();
        this.touchX = null;
    },
}));

/* ------------------------------------------------------------------
 | Gallery studio: auto-playing slideshow.
 | Autoplay pauses off-screen, in hidden tabs, on hover, in the lightbox
 | and (by default) for prefers-reduced-motion. Only the current and the
 | next photo are requested.
 * ------------------------------------------------------------------ */
Alpine.data('studio', (images = [], interval = 5000) => ({
    images,
    interval,
    index: 0,
    seen: images.map((_, i) => i === 0),
    hover: false,
    visible: false,
    hidden: document.hidden,
    lightbox: false,
    userPaused: reduceMotion,
    timer: null,
    touchX: null,
    get playing() {
        return !this.userPaused && !this.hover && this.visible && !this.hidden && !this.lightbox && this.images.length > 1;
    },
    init() {
        const io = new IntersectionObserver(([e]) => {
            this.visible = e.isIntersecting;
            if (e.isIntersecting && this.images.length > 1) this.seen[1] = true; // preload slide 2 once in view
            this.schedule();
        }, { threshold: 0.35 });
        io.observe(this.$refs.stage);
        document.addEventListener('visibilitychange', () => {
            this.hidden = document.hidden;
            this.schedule();
        });
        this.$watch('hover', () => this.schedule());
        this.$watch('lightbox', () => this.schedule());
        this.$watch('userPaused', () => this.schedule());
    },
    // Timer mirrors the CSS progress bar: pausing keeps the remaining time, resuming continues it.
    remaining: interval,
    startedAt: 0,
    running: false,
    schedule() {
        if (this.playing && !this.running) {
            this.running = true;
            this.startedAt = performance.now();
            this.timer = setTimeout(() => { this.running = false; this.next(); }, this.remaining);
        } else if (!this.playing && this.running) {
            this.running = false;
            clearTimeout(this.timer);
            this.remaining = Math.max(0, this.remaining - (performance.now() - this.startedAt));
        }
    },
    go(i) {
        const n = this.images.length;
        clearTimeout(this.timer);
        this.running = false;
        this.remaining = this.interval;
        this.index = (i + n) % n;
        this.seen[this.index] = true;
        this.seen[(this.index + 1) % n] = true; // preload the next one
        this.$nextTick(() => this.schedule());
    },
    next() { this.go(this.index + 1); },
    prev() { this.go(this.index - 1); },
    toggle() { this.userPaused = !this.userPaused; },
    dir() { return document.documentElement.dir; },
    openLightbox() {
        this.lightbox = true;
        document.documentElement.style.overflow = 'hidden';
        this.$nextTick(() => this.$refs.close?.focus());
    },
    closeLightbox() {
        this.lightbox = false;
        document.documentElement.style.overflow = '';
    },
    touchStart(e) { this.touchX = e.changedTouches[0].clientX; },
    touchEnd(e) {
        if (this.touchX === null) return;
        const dx = e.changedTouches[0].clientX - this.touchX;
        if (Math.abs(dx) > 45) (dx < 0) !== (this.dir() === 'rtl') ? this.next() : this.prev();
        this.touchX = null;
    },
    destroy() { clearTimeout(this.timer); },
}));

/* Copy / share helpers */
/**
 * Count-up number: animates from 0 to `target` the first time it scrolls into view.
 * The server renders the final value, so no-JS visitors and crawlers still see it.
 */
Alpine.data('countUp', (target = 0, duration = 1800) => ({
    display: new Intl.NumberFormat('en-US').format(target),
    init() {
        if (!target || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const fmt = new Intl.NumberFormat('en-US');
        this.display = '0';
        const run = () => {
            const start = performance.now();
            const tick = (now) => {
                const t = Math.min(1, (now - start) / duration);
                const eased = t === 1 ? 1 : 1 - Math.pow(2, -10 * t); // easeOutExpo
                this.display = fmt.format(Math.round(target * eased));
                if (t < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        };
        const io = new IntersectionObserver((entries) => {
            if (entries.some((e) => e.isIntersecting)) { io.disconnect(); run(); }
        }, { threshold: 0.4 });
        io.observe(this.$el);
    },
}));

Alpine.data('copyable', (text) => ({
    copied: false,
    async copy() {
        try {
            await navigator.clipboard.writeText(text);
        } catch (_) {
            const t = document.createElement('textarea');
            t.value = text;
            document.body.appendChild(t);
            t.select();
            document.execCommand('copy');
            t.remove();
        }
        this.copied = true;
        setTimeout(() => (this.copied = false), 1800);
    },
    canShare: typeof navigator !== 'undefined' && !!navigator.share,
    async share(title) {
        try {
            await navigator.share({ title, url: text });
        } catch (_) {}
    },
}));

/* Review actions in the admin (accept / reject without full reload). */
Alpine.data('reviewAction', (acceptUrl, rejectUrl, status) => ({
    status,
    busy: false,
    error: null,
    async send(kind) {
        if (this.busy || this.status !== 'pending') return;
        if (kind === 'reject' && !confirm(document.documentElement.lang === 'ar' ? 'رفض هذا التسجيل؟ لا يمكن التراجع.' : 'Reject this registration? This cannot be undone.')) return;
        this.busy = true;
        this.error = null;
        try {
            const res = await fetch(kind === 'accept' ? acceptUrl : rejectUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Request failed');
            this.status = data.status;
            window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', text: data.message } }));
            window.dispatchEvent(new CustomEvent('live:refresh'));
        } catch (e) {
            this.error = e.message;
            window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', text: e.message } }));
        } finally {
            this.busy = false;
        }
    },
}));

Alpine.data('toaster', () => ({
    toasts: [],
    add(t) {
        const id = Date.now() + Math.random();
        this.toasts.push({ id, ...t });
        setTimeout(() => this.remove(id), 4000);
    },
    remove(id) {
        this.toasts = this.toasts.filter((t) => t.id !== id);
    },
}));

Alpine.plugin(collapse);
window.Alpine = Alpine;
window.formatClock = formatClock;
Alpine.start();

document.addEventListener('DOMContentLoaded', initReveal);
if (document.readyState !== 'loading') initReveal();
