@props(['disabled' => false])

@if ($attributes->get('type') === 'file')
    <input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'text-sm text-zinc-700 file:me-3 file:rounded-md file:border-0 file:bg-zinc-800/5 file:px-3 file:py-2 file:text-sm file:font-medium file:text-zinc-800 hover:file:bg-zinc-800/10']) !!}>
@else
    <flux:input :attributes="$attributes->merge(['disabled' => $disabled])" />
@endif
