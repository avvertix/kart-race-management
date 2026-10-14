<div {{ $attributes }}>
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" size="sm" icon-trailing="chevron-down">
            <span class="inline-block font-mono uppercase">{{ $currentLanguage }}</span>
        </flux:button>

        <flux:menu>
            <flux:menu.item :href="route('language.change', ['lang' => 'it'])" :icon:trailing="app()->getLocale() === 'it' ? 'check' : null">{{ __('Italiano') }}</flux:menu.item>
            <flux:menu.item :href="route('language.change', ['lang' => 'en'])" :icon:trailing="app()->getLocale() === 'en' ? 'check' : null">{{ __('English') }}</flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</div>
