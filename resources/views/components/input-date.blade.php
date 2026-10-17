@props(['value' => null, 'disabled' => false, 'mask' => trans('date-input.mask'), 'placeholder' => trans('date-input.placeholder'), 'format' => trans('date-input.format') ])

@php
    try {
        $value = Date::normalizeToFormat($value, $format);
    } catch (\ValueError|\Carbon\Exceptions\InvalidFormatException $th) {
    }
@endphp

<flux:input :mask="$mask" :attributes="$attributes->merge(['x-data' => '', 'disabled' => $disabled, 'placeholder' => $placeholder, 'value' => $value])" />
