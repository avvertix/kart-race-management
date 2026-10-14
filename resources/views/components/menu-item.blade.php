{{--
    Flux menu item with an optional icon slot and a description line.
    Usage: <x-menu-item :href="..." :description="__('...')"><x-slot:icon><x-ri-... /></x-slot:icon>Label</x-menu-item>
--}}
@props(['icon' => null, 'description' => null])

<flux:menu.item :attributes="$attributes->class('items-start! gap-2')">
    @if ($icon)
        <span class="shrink-0 text-zinc-400 [[data-active]_&]:text-zinc-800 *:size-5">{{ $icon }}</span>
    @endif

    <span class="min-w-0">
        <span class="block">{{ $slot }}</span>
        @if ($description)
            <span class="block text-xs font-normal text-zinc-500">{{ $description }}</span>
        @endif
    </span>
</flux:menu.item>
