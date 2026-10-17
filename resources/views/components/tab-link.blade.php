@props(['active' => false])

<flux:navbar.item :attributes="$attributes" :current="(bool) $active">{{ $slot }}</flux:navbar.item>
