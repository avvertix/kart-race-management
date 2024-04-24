@props(['value'])

@php
    $styles = [
        'active' => 'inline-flex items-center rounded-md bg-lime-50 px-2 py-1 text-xs font-medium text-lime-800 ring-1 ring-inset ring-lime-600/20',
        'registration_open' => 'inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10',
        'scheduled' => 'inline-flex items-center rounded-md bg-zinc-50 px-2 py-1 text-xs font-medium text-zinc-600 ring-1 ring-inset ring-zinc-500/10',
        'concluded' => 'inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20',
        'canceled' => 'inline-flex items-center rounded-md bg-pink-50 px-2 py-1 text-xs font-medium text-pink-700 ring-1 ring-inset ring-pink-700/10',
    ];
@endphp

<span {{ $attributes->merge(['class' => $styles[$value] ?? $styles['scheduled']]) }}>
    {{ trans("race-statuses.{$value}") }}
</span>
