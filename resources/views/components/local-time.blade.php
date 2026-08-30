@props([
    'date' => null,
    'format' => 'datetime',
    'timezone' => null,
])
<time
    data-local-time
    datetime="{{ $isoUtc }}"
    data-format="{{ $format }}"
    {{ $attributes->merge(['class' => 'local-time']) }}
>{{ $serverFormatted }}</time>
