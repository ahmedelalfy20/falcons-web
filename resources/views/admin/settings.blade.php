@php
    $labels = [
        'hero' => [__('Homepage hero'), ['badge' => __('Badge'), 'title' => __('Headline'), 'subtitle' => __('Subtitle'), 'primary_cta' => __('Primary button'), 'secondary_cta' => __('Secondary button')]],
        'founder' => [__('Founder'), ['name' => __('Name'), 'title' => __('Title'), 'quote' => __('Hero quote'), 'bio_1' => __('Biography — paragraph 1'), 'bio_2' => __('Biography — paragraph 2'), 'mission_quote' => __('Mission quote')]],
        'about' => [__('About'), ['story_1' => __('Story — paragraph 1'), 'story_2' => __('Story — paragraph 2'), 'story_3' => __('Story — paragraph 3'), 'quote' => __('Quote')]],
        'contact' => [__('Contact'), ['whatsapp' => __('WhatsApp number'), 'whatsapp_message' => __('Default WhatsApp message'), 'register_url' => __('“Register now” link'), 'email' => __('Email')]],
        'app' => [__('Mobile app'), ['play_url' => __('Google Play link'), 'appstore_url' => __('App Store link')]],
        'social' => [__('Social links'), ['telegram' => 'Telegram', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube']],
        'stats' => [__('Numbers'), ['active_members' => __('Active members'), 'countries' => __('Countries'), 'students_trained' => __('Students trained'), 'satisfaction_rate' => __('Satisfaction rate (%)'), 'years_experience' => __('Years of experience')]],
    ];
    $long = ['subtitle', 'quote', 'bio_1', 'bio_2', 'mission_quote', 'story_1', 'story_2', 'story_3', 'whatsapp_message'];
    $vision = (array) site('vision', [], false);
    $tabs = array_merge(array_keys($groups), ['vision', 'free']);
@endphp
<x-layouts.admin :heading="__('Settings')" :subheading="__('Website content and contact details. Arabic fields fall back to English when empty.')">
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" x-data="{ tab: 'hero' }" class="grid gap-5 lg:grid-cols-12">
        @csrf @method('PUT')
        <nav class="-mx-4 flex gap-1 overflow-x-auto px-4 lg:col-span-3 lg:mx-0 lg:flex-col lg:px-0" aria-label="{{ __('Settings sections') }}">
            @foreach ($tabs as $t)
                <button type="button" @click="tab = '{{ $t }}'" class="flex min-h-11 shrink-0 items-center rounded-xl px-4 text-start text-sm font-medium transition-colors"
                        :class="tab === '{{ $t }}' ? 'bg-white/[0.07] text-fg' : 'text-fg-muted hover:text-fg'">
                    {{ $labels[$t][0] ?? ($t === 'vision' ? __('Vision & Mission') : __('Free courses')) }}
                    @if ($errors->hasAny(collect($groups[$t] ?? [])->keys()->map(fn ($f) => "$t.$f")->all()))<span class="ms-2 size-2 rounded-full bg-danger"></span>@endif
                </button>
            @endforeach
        </nav>

        <div class="lg:col-span-9">
            @foreach ($groups as $group => $fields)
                <section class="card card-pad space-y-5" x-show="tab === '{{ $group }}'" @if ($group !== 'hero') x-cloak @endif>
                    <h2 class="text-lg font-bold">{{ $labels[$group][0] }}</h2>
                    @if ($group === 'founder')
                        <div class="flex items-center gap-4">
                            <img src="{{ media(site('founder.image', 'media/founder.webp', false)) }}" alt="" class="size-20 rounded-2xl bg-[#ecebe8] object-cover object-top">
                            <div class="flex-1"><label for="founder_image" class="label">{{ __('Founder photo') }}</label><input id="founder_image" type="file" name="founder_image" accept="image/*" class="block w-full text-sm text-fg-muted file:me-3 file:rounded-full file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-fg"><p class="hint">{{ __('Use a PNG with a transparent background (no backdrop) so it blends into the homepage. Converted to WebP automatically.') }}</p></div>
                        </div>
                    @endif
                    @foreach ($fields as $field => $translatable)
                        @php $label = $labels[$group][1][$field] ?? $field; $isLong = in_array($field, $long, true); $type = $group === 'stats' ? 'number' : (in_array($field, ['register_url']) || $group === 'social' ? 'url' : ($field === 'email' ? 'email' : 'text')); @endphp
                        <div @class(['grid gap-4', 'sm:grid-cols-2' => $translatable])>
                            <x-field :name="$group.'.'.$field" :type="$type" :label="$translatable ? $label.' — '.__('English') : $label" :value="site($group.'.'.$field, null, false)" :textarea="$isLong" rows="3" :dir="in_array($type, ['url', 'email']) || $field === 'whatsapp' ? 'ltr' : null" />
                            @if ($translatable)
                                <x-field :name="$group.'.'.$field.'_ar'" :label="$label.' — '.__('Arabic')" :value="site($group.'.'.$field.'_ar', null, false)" :textarea="$isLong" rows="3" dir="rtl" />
                            @endif
                        </div>
                    @endforeach
                </section>
            @endforeach

            <section class="card card-pad space-y-5" x-show="tab === 'vision'" x-cloak>
                <h2 class="text-lg font-bold">{{ __('Vision & Mission') }}</h2>
                <p class="text-sm text-fg-muted">{{ __('One point per line.') }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="vision" :label="__('Vision — English')" :value="implode("\n", $vision['vision'] ?? [])" textarea rows="5" />
                    <x-field name="vision_ar" :label="__('Vision — Arabic')" :value="implode("\n", $vision['vision_ar'] ?? [])" textarea rows="5" dir="rtl" />
                    <x-field name="mission" :label="__('Mission — English')" :value="implode("\n", $vision['mission'] ?? [])" textarea rows="5" />
                    <x-field name="mission_ar" :label="__('Mission — Arabic')" :value="implode("\n", $vision['mission_ar'] ?? [])" textarea rows="5" dir="rtl" />
                </div>
            </section>

            <section class="card card-pad space-y-5" x-show="tab === 'free'" x-cloak>
                <h2 class="text-lg font-bold">{{ __('Free courses') }}</h2>
                <x-toggle name="free_courses_enabled" :label="__('Free courses page enabled')" :checked="(bool) site('free_courses.enabled', true, false)" />
                <x-field name="free_courses_code" type="password" :label="__('New invite code')" autocomplete="new-password" :hint="__('Leave empty to keep the current code. Codes are stored hashed and checked on the server.')" class="max-w-sm" />
            </section>

            <div class="sticky bottom-0 mt-5 flex justify-end border-t border-line bg-ink-950/90 py-4 backdrop-blur">
                <button class="btn btn-primary">{{ __('Save settings') }}</button>
            </div>
        </div>
    </form>
</x-layouts.admin>
