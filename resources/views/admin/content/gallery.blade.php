<x-layouts.admin :heading="__('Gallery')" :subheading="__('Photos are resized, converted to WebP and given lightweight thumbnails on upload.')">
    <div class="space-y-5">
        @foreach (['academy' => [__('Academy gallery'), $academy]] as $key => [$title, $images])
            <section class="card card-pad" aria-labelledby="g-{{ $key }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="g-{{ $key }}" class="font-bold">{{ $title }} <span class="num ms-1 text-sm font-normal text-fg-subtle">{{ $images->count() }}</span></h2>
                    <form method="POST" action="{{ route('admin.gallery.store') }}" enctype="multipart/form-data" class="flex items-center gap-2" x-data="{ n: 0 }">
                        @csrf
                        <input type="hidden" name="collection" value="{{ $key }}">
                        <label class="btn btn-outline btn-sm cursor-pointer"><x-icon name="plus" class="size-4" /><span x-text="n ? @js(__('Selected:')) + ' ' + n : @js(__('Choose photos'))"></span>
                            <input type="file" name="photos[]" accept="image/*" multiple class="sr-only" @change="n = $event.target.files.length"></label>
                        <button class="btn btn-primary btn-sm" x-show="n > 0" x-cloak>{{ __('Upload') }}</button>
                    </form>
                </div>
                @if ($images->isEmpty())
                    <x-empty icon="image" :title="__('No photos yet')" />
                @else
                    <ul class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-6" role="list">
                        @foreach ($images as $img)
                            <li class="group relative overflow-hidden rounded-xl bg-ink-800">
                                <img src="{{ media($img->thumb_path ?: $img->path) }}" alt="" loading="lazy" class="aspect-square w-full object-cover">
                                <form method="POST" action="{{ route('admin.gallery.destroy', $img) }}" class="absolute end-1.5 top-1.5" onsubmit="return confirm(@js(__('Remove this photo?')))">
                                    @csrf @method('DELETE')
                                    <button class="flex size-9 items-center justify-center rounded-full bg-ink-950/80 text-fg opacity-100 backdrop-blur transition-opacity hover:text-danger sm:opacity-0 sm:group-hover:opacity-100 sm:focus:opacity-100" aria-label="{{ __('Remove photo') }}"><x-icon name="trash" class="size-4" /></button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @error('photos.*')<p class="field-error">{{ $message }}</p>@enderror
            </section>
        @endforeach
    </div>
</x-layouts.admin>
