@props(['value' => null, 'disabled' => false, 'mask' => trans('date.mask'), 'placeholder' => trans('date.placeholder'), 'format' => trans('date.format') ])

@php
    $value = Date::normalizeToFormat($value, $format);
@endphp

<input x-data x-mask="{{ $mask }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => 'border-zinc-300 focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50 rounded-md shadow-sm', 'placeholder' => $placeholder, 'value' => $value]) }}>
