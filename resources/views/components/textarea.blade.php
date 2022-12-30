@props(['disabled' => false])

<textarea {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-zinc-300 focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50 rounded-md shadow-sm']) !!}>{{ $slot }}</textarea>
