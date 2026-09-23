@php $editing = $company->exists; @endphp
<x-layouts.admin :heading="$editing ? __('Edit :name', ['name' => $company->name]) : __('Add company')">
    <x-slot:breadcrumb><a href="{{ route('admin.companies.index') }}" class="hover:text-fg">{{ __('Companies') }}</a> / {{ $editing ? $company->name : __('New') }}</x-slot:breadcrumb>

    <form method="POST" action="{{ $editing ? route('admin.companies.update', $company) : route('admin.companies.store') }}" enctype="multipart/form-data" class="max-w-3xl space-y-5"
          x-data="{ preview: @js($company->logo ? media($company->logo) : null), light: @js((bool) old('on_light', $company->on_light)) }">
        @csrf
        @if ($editing) @method('PUT') @endif
        <section class="card card-pad space-y-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <span class="flex h-28 w-full shrink-0 items-center justify-center rounded-2xl border border-line p-4 transition-colors sm:w-52" :class="light ? 'bg-white' : 'bg-ink-950'">
                    <template x-if="preview"><img :src="preview" alt="" class="max-h-full max-w-full object-contain"></template>
                    <template x-if="!preview"><x-icon name="image" class="size-8 text-fg-subtle" /></template>
                </span>
                <div class="min-w-0 flex-1">
                    <label for="logo" class="label">{{ __('Logo') }}@unless ($editing)<span class="text-danger" aria-hidden="true"> *</span>@endunless</label>
                    <input id="logo" type="file" name="logo" accept="image/png,image/webp,image/jpeg" @unless ($editing) required @endunless
                           class="block w-full text-sm text-fg-muted file:me-3 file:rounded-full file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-fg"
                           @change="const f = $event.target.files[0]; if (f) preview = URL.createObjectURL(f)">
                    <p class="hint">{{ __('A PNG with a transparent background looks best. It is resized and converted to WebP automatically.') }}</p>
                    @error('logo')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="name" :label="__('Name — English')" :value="$company->name" required maxlength="120" />
                <x-field name="name_ar" :label="__('Name — Arabic')" :value="$company->name_ar" dir="rtl" maxlength="120" />
            </div>
            <x-field name="url" type="url" :label="__('Website (optional)')" :value="$company->url" dir="ltr" placeholder="https://" maxlength="255" :hint="__('If set, clicking the logo opens the company website.')" />
        </section>
        <section class="card card-pad grid gap-4 sm:grid-cols-3">
            <x-field name="sort_order" type="number" :label="__('Order')" :value="$company->sort_order" min="0" max="1000" required />
            <div class="flex items-end"><x-toggle name="is_active" :label="__('Visible on website')" :checked="$company->is_active" class="w-full" /></div>
            <div class="flex items-end" @change="light = $event.target.checked"><x-toggle name="on_light" :label="__('White background')" :hint="__('For dark logos')" :checked="$company->on_light" class="w-full" /></div>
        </section>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.companies.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
            <button class="btn btn-primary">{{ $editing ? __('Save company') : __('Add company') }}</button>
        </div>
    </form>
    @if ($editing)
        <form method="POST" action="{{ route('admin.companies.destroy', $company) }}" class="mt-8 max-w-3xl rounded-[var(--radius-card)] border border-danger/25 p-5" onsubmit="return confirm(@js(__('Delete this company?')))">
            @csrf @method('DELETE')
            <p class="font-bold text-danger">{{ __('Delete company') }}</p>
            <button class="btn btn-danger btn-sm mt-3"><x-icon name="trash" class="size-4" />{{ __('Delete') }}</button>
        </form>
    @endif
</x-layouts.admin>
