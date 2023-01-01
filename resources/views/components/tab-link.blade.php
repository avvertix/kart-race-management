@props(['active'])

@php
$classes = ($active ?? false)
            ? 'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm border-orange-500 text-orange-600'
            : 'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm text-zinc-500 hover:text-zinc-700 hover:border-zinc-400';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
{{-- aria-current="page" --}}