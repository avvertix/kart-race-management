@props(['value'])

@php
    $styles = [
        'active' => 'inline-block px-2 py-1 text-sm rounded-full bg-lime-100 text-lime-800',
        'registration_open' => 'inline-block px-2 py-1 text-sm rounded-full bg-blue-100 text-blue-800',
        'scheduled' => 'inline-block px-2 py-1 text-sm rounded-full bg-zinc-100 text-zinc-800',
        'concluded' => 'inline-block px-2 py-1 text-sm rounded-full bg-zinc-100 text-zinc-800',
    ];
@endphp

<span {{ $attributes->merge(['class' => $styles[$value] ?? $styles['scheduled']]) }}>
    {{ trans("race-statuses.{$value}") }}
</span>
