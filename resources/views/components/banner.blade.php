@props(['style' => session('flash.bannerStyle', 'success'), 'message' => session('flash.banner')])

@php
    [$variant, $icon] = match ($style) {
        'success' => ['success', 'check-circle'],
        'danger' => ['danger', 'exclamation-circle'],
        'warning' => ['warning', 'exclamation-triangle'],
        default => ['secondary', 'information-circle'],
    };
@endphp

@if (filled($message))
    <div x-data="{ show: true }" x-show="show" {{ $attributes }}>
        <flux:callout :$variant :$icon :text="$message">
            <x-slot name="controls">
                <flux:button icon="x-mark" variant="ghost" size="sm" :aria-label="__('Dismiss')" x-on:click="show = false" />
            </x-slot>
        </flux:callout>
    </div>
@endif
