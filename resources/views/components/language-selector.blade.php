<div {{ $attributes }}>
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" size="sm" icon-trailing="chevron-down">
            <span class="inline-block font-mono uppercase">{{ $currentLanguage }}</span>
            <span class="hidden xl:inline-block">{{ __('Language') }}</span>
        </flux:button>

        <flux:menu>
            <flux:menu.item :href="route('language.change', ['lang' => 'it'])">{{ __('Italiano') }}</flux:menu.item>
            <flux:menu.item :href="route('language.change', ['lang' => 'en'])">{{ __('English') }}</flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</div>
