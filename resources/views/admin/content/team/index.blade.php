<x-layouts.admin :heading="__('Team profiles')" :subheading="__('The leaders shown on the homepage. Each one has a public profile page.')">
    <x-slot:actions><a href="{{ route('admin.team.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" />{{ __('Add profile') }}</a></x-slot:actions>
    @if ($members->isEmpty())
        <div class="card"><x-empty icon="user" :title="__('No profiles yet')" /></div>
    @else
        <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" role="list">
            @foreach ($members as $m)
                <li class="card flex items-center gap-4 p-3 pe-4">
                    <img src="{{ media($m->image) }}" alt="" loading="lazy" class="size-16 shrink-0 rounded-xl bg-[#ecebe8] object-cover object-top">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold">{{ $m->name }}</p>
                        <p class="truncate text-sm text-fg-muted">{{ $m->role }}@if ($m->city) · {{ $m->city }}@endif</p>
                        <p class="mt-1 flex flex-wrap gap-1.5">
                            @unless ($m->is_active)<span class="badge badge-neutral">{{ __('Hidden') }}</span>@endunless
                            @if ($m->hasBio())<span class="badge badge-accepted">{{ __('Bio added') }}</span>@else<span class="badge badge-pending">{{ __('Bio missing') }}</span>@endif
                        </p>
                    </div>
                    <a href="{{ route('admin.team.edit', $m) }}" class="btn btn-outline btn-sm shrink-0"><x-icon name="edit" class="size-4" />{{ __('Edit') }}</a>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.admin>
