{{-- Inline accept / reject. Buttons render only with permission; the server re-checks every request. --}}
@php
    $canAccept = auth()->user()->can('accept', $r);
    $canReject = auth()->user()->can('reject', $r);
@endphp
<div x-data="reviewAction(@js(route('admin.registrations.accept', $r)), @js(route('admin.registrations.reject', $r)), @js($r->status->value))" class="flex items-center justify-end gap-2">
    <template x-if="status === 'pending'">
        <div class="flex items-center gap-2">
            @if ($canReject)
                <button type="button" class="btn btn-danger btn-sm" :disabled="busy" @click="send('reject')" aria-label="{{ __('Reject registration #:id', ['id' => $r->id]) }}"><x-icon name="x" class="size-4" /><span class="{{ $compact ?? false ? 'sr-only' : '' }}">{{ __('Reject') }}</span></button>
            @endif
            @if ($canAccept)
                <button type="button" class="btn btn-success btn-sm" :disabled="busy" @click="send('accept')" aria-label="{{ __('Accept registration #:id', ['id' => $r->id]) }}"><x-icon name="check" class="size-4" /><span class="{{ $compact ?? false ? 'sr-only' : '' }}">{{ __('Accept') }}</span></button>
            @endif
            @unless ($canAccept || $canReject)<span class="badge badge-pending">{{ __('Pending') }}</span>@endunless
        </div>
    </template>
    <template x-if="status !== 'pending'">
        <span class="badge" :class="'badge-' + status" x-text="{ accepted: @js(__('Accepted')), rejected: @js(__('Rejected')) }[status]"></span>
    </template>
</div>
