@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-zinc-300 focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50 rounded-md shadow-xs']) !!}>
