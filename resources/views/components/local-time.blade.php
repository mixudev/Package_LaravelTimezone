@props([
    'date' => null,
    'format' => 'datetime',
    'timezone' => null,
    'live' => false,
])
<time
    data-local-time
    datetime="{{ $isoUtc }}"
    data-format="{{ $format }}"
    @if($live) data-live="true" @endif
    @if($isNow) data-now="true" @endif
    {{ $attributes->merge(['class' => 'local-time']) }}
>{{ $serverFormatted }}</time>
