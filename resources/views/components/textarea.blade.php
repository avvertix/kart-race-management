@props(['disabled' => false])

<flux:textarea :attributes="$attributes->merge(['disabled' => $disabled])">{{ $slot }}</flux:textarea>
