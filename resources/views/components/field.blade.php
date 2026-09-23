@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'required' => false,
    'textarea' => false,
    'rows' => 4,
    'dir' => null,
])
@php
    // Dot notation (hero.title) maps to bracket names (hero[title]) and old('hero.title').
    $key = $name;
    $inputName = str_contains($name, '.') ? preg_replace('/\.([^.]+)/', '[$1]', $name) : $name;
    $id = 'f-'.str_replace(['.', '[', ']'], '-', $name);
    $error = $errors->first($key);
    $current = $type === 'password' ? null : old($key, $value);
@endphp
<div {{ $attributes->only('class')->class(['min-w-0']) }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
    @endif
    @if ($textarea)
        <textarea id="{{ $id }}" name="{{ $inputName }}" rows="{{ $rows }}" @if($dir) dir="{{ $dir }}" @endif
                  @required($required) @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif($hint) aria-describedby="{{ $id }}-hint" @endif
                  {{ $attributes->except('class')->class(['input', 'input-error' => $error]) }}>{{ $current }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $inputName }}" type="{{ $type }}" value="{{ $current }}" @if($dir) dir="{{ $dir }}" @endif
               @required($required) @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif($hint) aria-describedby="{{ $id }}-hint" @endif
               {{ $attributes->except('class')->class(['input', 'input-error' => $error]) }}>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="field-error"><x-icon name="alert" class="size-4 shrink-0" />{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="hint">{{ $hint }}</p>
    @endif
</div>
