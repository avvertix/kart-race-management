@props(['value' => null, 'disabled' => false, 'mask' => trans('date-input.mask'), 'placeholder' => trans('date-input.placeholder'), 'format' => trans('date-input.format') ])

@php
    $value = Date::normalizeToFormat($value, $format);
@endphp

<input x-data x-mask="{{ $mask }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => 'border-zinc-300 focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50 rounded-md shadow-sm', 'placeholder' => $placeholder, 'value' => $value]) }}>
