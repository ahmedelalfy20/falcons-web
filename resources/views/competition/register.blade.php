@php
    $requireEmail = (bool) $competition->config('require_email');
    $requireProof = (bool) $competition->config('require_transfer_proof');
    $collectCity = (bool) $competition->config('collect_city');
@endphp
<x-layouts.site :title="__('Register')">
    <section class="relative pt-24 pb-16 sm:pt-28">
        <div class="bg-glow pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="container-x relative">
            <div class="mx-auto grid max-w-5xl gap-8 lg:grid-cols-12 lg:gap-12">
                {{-- Context: who invited you, which round, time left --}}
                <aside class="lg:col-span-5">
                    <p class="eyebrow">{{ $competition->tr('name') }}</p>
                    <h1 class="h-section mt-4">{{ __('Join through :name', ['name' => $leader->name]) }}</h1>
                    <p class="mt-4 text-fg-muted">{{ __('Fill in your details to register. Our team will review your registration and you can track its status on the next page.') }}</p>

                    <dl class="card mt-8 divide-y divide-line">
                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <dt class="text-sm text-fg-muted">{{ __('Invited by') }}</dt>
                            <dd class="flex items-center gap-2 font-semibold"><x-icon name="check-circle" class="size-4 text-brand-400" />{{ $leader->name }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <dt class="text-sm text-fg-muted">{{ __('Referral code') }}</dt>
                            <dd class="num font-semibold tracking-wider" dir="ltr">{{ $leader->unique_code }}</dd>
                        </div>
                        <div class="px-5 py-4">
                            <dt class="sr-only">{{ __('Round') }}</dt>
                            <dd>@include('competition.partials.timer', ['round' => $round])</dd>
                        </div>
                    </dl>
                </aside>

                <div class="lg:col-span-7">
                    @if (session('error'))
                        <x-alert level="danger" class="mb-5">{{ session('error') }}</x-alert>
                    @endif
                    <form method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data" class="card card-pad space-y-5" novalidate
                          x-data="{ busy: false }" @submit="busy = true"
                          @round:expired.window="$refs.expired.hidden = false">
                        @csrf
                        <input type="hidden" name="ref" value="{{ $ref }}">
                        {{-- Honeypot --}}
                        <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                        <p x-ref="expired" hidden class="rounded-xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-amber-100">{{ __('The timer has reached zero. Registration may now be closed.') }}</p>

                        <h2 class="text-lg font-bold">{{ __('Your details') }}</h2>
                        <x-field name="full_name" :label="__('Full name')" autocomplete="name" required maxlength="120" />
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-field name="phone" type="tel" :label="__('Phone number')" autocomplete="tel" inputmode="tel" dir="ltr" required maxlength="25" placeholder="01XXXXXXXXX" :hint="__('We use your phone to make sure each person registers once.')" />
                            <x-field name="email" type="email" :label="$requireEmail ? __('Email (Gmail)') : __('Email (optional)')" autocomplete="email" inputmode="email" dir="ltr" :required="$requireEmail" maxlength="190" placeholder="name@gmail.com" />
                        </div>

                        {{-- Payment transfer screenshot --}}
                        <div x-data="{ preview: null, name: '', size: '', over: false,
                                       pick(f) { if (!f) return; this.name = f.name; this.size = f.size < 1048576 ? Math.max(1, Math.round(f.size / 1024)) + ' KB' : (f.size / 1048576).toFixed(1) + ' MB';
                                                 if (this.preview) URL.revokeObjectURL(this.preview); this.preview = f.type.startsWith('image/') ? URL.createObjectURL(f) : null; },
                                       clear() { this.$refs.file.value = ''; if (this.preview) URL.revokeObjectURL(this.preview); this.preview = null; this.name = ''; } }">
                            <label for="transfer_screenshot" class="label">{{ __('Transfer screenshot') }}@if ($requireProof)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
                            <label for="transfer_screenshot"
                                   class="relative flex min-h-36 cursor-pointer flex-col items-center justify-center gap-2 overflow-hidden rounded-2xl border-2 border-dashed px-4 py-6 text-center transition-colors @error('transfer_screenshot') border-danger/70 @else border-line-strong @enderror"
                                   :class="over ? 'border-brand-500 bg-brand-500/[0.06]' : 'hover:border-fg-subtle'"
                                   @dragover.prevent="over = true" @dragleave.prevent="over = false"
                                   @drop.prevent="over = false; $refs.file.files = $event.dataTransfer.files; pick($event.dataTransfer.files[0])">
                                <template x-if="!preview">
                                    <span class="flex flex-col items-center gap-2">
                                        <span class="flex size-11 items-center justify-center rounded-full bg-brand-500/10 text-brand-300"><x-icon name="image" class="size-5" /></span>
                                        <span class="text-sm font-semibold">{{ __('Upload the screenshot of your transfer') }}</span>
                                        <span class="text-xs text-fg-subtle">{{ __('Tap to choose, or drag it here · JPG, PNG or WebP · max 10 MB') }}</span>
                                    </span>
                                </template>
                                <template x-if="preview">
                                    <span class="flex w-full items-center gap-4 text-start">
                                        <img :src="preview" alt="" class="h-24 w-20 shrink-0 rounded-lg bg-ink-800 object-cover">
                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-center gap-1.5 text-sm font-semibold text-success"><x-icon name="check-circle" class="size-4" />{{ __('Screenshot attached') }}</span>
                                            <span class="mt-1 block truncate text-xs text-fg-muted" x-text="name"></span>
                                            <span class="block text-xs text-fg-subtle" x-text="size"></span>
                                        </span>
                                        <button type="button" class="btn btn-ghost btn-sm" @click.prevent="clear()">{{ __('Change') }}</button>
                                    </span>
                                </template>
                            </label>
                            <input id="transfer_screenshot" x-ref="file" type="file" name="transfer_screenshot" accept="image/jpeg,image/png,image/webp" class="sr-only" @if ($requireProof) required @endif
                                   @change="pick($event.target.files[0])" @error('transfer_screenshot') aria-invalid="true" aria-describedby="transfer-error" @enderror>
                            @error('transfer_screenshot')
                                <p id="transfer-error" class="field-error"><x-icon name="alert" class="size-4 shrink-0" />{{ $message }}</p>
                            @else
                                @if ($errors->any())
                                    <p class="mt-1.5 flex items-center gap-1.5 text-sm text-warning"><x-icon name="info" class="size-4 shrink-0" />{{ __('Please attach the screenshot again after fixing the errors above.') }}</p>
                                @else
                                    <p class="hint">{{ __('Only our review team can see this image.') }}</p>
                                @endif
                            @enderror
                        </div>

                        {{-- City (mandatory) --}}
                        <x-field name="city" :label="__('City')" autocomplete="address-level2" required maxlength="80" placeholder="{{ __('e.g. Cairo, Alexandria...') }}" />

                        {{-- Team Selection (mandatory) --}}
                        <div class="space-y-2.5" x-data="{ selectedTeam: @js(old('team', '')) }">
                            <div class="flex items-center justify-between">
                                <label class="label !mb-0 font-bold text-fg">{{ __('Select your team') }}<span class="text-danger" aria-hidden="true"> *</span></label>
                                <span class="text-xs text-fg-subtle">{{ __('Choose one') }}</span>
                            </div>

                            @php
                                $teams = [
                                    [
                                        'id' => 'Million Team',
                                        'name' => 'Million Team',
                                        'name_ar' => 'مليون تيم',
                                        'logo' => 'media/companies/million-team.webp',
                                    ],
                                    [
                                        'id' => '3AQRAB',
                                        'name' => '3AQRAB',
                                        'name_ar' => 'عقرب',
                                        'logo' => 'media/companies/3aqrab.webp',
                                    ],
                                    [
                                        'id' => 'Mega Team',
                                        'name' => 'Mega Team',
                                        'name_ar' => 'ميجا تيم',
                                        'logo' => 'media/companies/mega-team.webp',
                                    ],
                                ];
                            @endphp

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                @foreach ($teams as $t)
                                    <label class="group relative flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border p-4 text-center transition-all duration-200"
                                           :class="selectedTeam === @js($t['id']) 
                                                ? 'border-brand-400 bg-brand-500/[0.12] ring-2 ring-brand-400/40 shadow-lg shadow-brand-500/10' 
                                                : 'border-white/10 bg-ink-900/60 hover:border-white/25 hover:bg-white/[0.02]'">
                                        <input type="radio" name="team" value="{{ $t['id'] }}" class="sr-only"
                                               x-model="selectedTeam"
                                               @checked(old('team') === $t['id']) required>
                                        
                                        {{-- Team Logo --}}
                                        <div class="flex h-14 w-full items-center justify-center px-2">
                                            <img src="{{ asset($t['logo']) }}" alt="{{ $t['name'] }}" class="max-h-12 max-w-[120px] object-contain transition-transform group-hover:scale-105">
                                        </div>

                                        {{-- Team Name --}}
                                        <div class="space-y-0.5">
                                            <span class="block text-sm font-bold tracking-wide" :class="selectedTeam === @js($t['id']) ? 'text-brand-200' : 'text-fg'">{{ $t['name'] }}</span>
                                            <span class="block text-xs text-fg-subtle" :class="selectedTeam === @js($t['id']) ? 'text-brand-300/80' : 'text-fg-subtle'">{{ $t['name_ar'] }}</span>
                                        </div>

                                        {{-- Selected Check indicator --}}
                                        <div class="absolute top-2.5 end-2.5 flex size-5 items-center justify-center rounded-full border transition-all"
                                             :class="selectedTeam === @js($t['id']) ? 'border-brand-400 bg-brand-400 text-ink-950 scale-100' : 'border-white/20 bg-black/40 text-transparent scale-90 opacity-40 group-hover:opacity-80'">
                                            <x-icon name="check" class="size-3" stroke="3" />
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            @error('team')
                                <p class="field-error"><x-icon name="alert" class="size-4 shrink-0" />{{ $message }}</p>
                            @enderror
                        </div>
                        <label class="flex items-start gap-3 text-sm leading-relaxed text-fg-muted">
                            <input type="checkbox" name="consent" value="1" @checked(old('consent')) class="checkbox mt-0.5" required>
                            <span>{{ __('I confirm my details are correct and agree that Falcons may contact me about this competition.') }}</span>
                        </label>
                        @error('consent')<p class="field-error">{{ $message }}</p>@enderror

                        <button type="submit" class="btn btn-primary btn-lg w-full" :disabled="busy">
                            <span x-show="!busy">{{ __('Submit registration') }}</span>
                            <span x-show="busy" x-cloak class="inline-flex items-center gap-2"><span class="size-4 animate-spin rounded-full border-2 border-ink-950/30 border-t-ink-950"></span>{{ __('Submitting…') }}</span>
                        </button>
                        <p class="text-center text-xs text-fg-subtle">{{ __('Your registration starts as pending until our team reviews it.') }}</p>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <div x-data="poller(@js(route('live.competition')), 15000)"></div>
</x-layouts.site>
