<section {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <p data-section="true" class="font-mono text-xs/6 font-medium tracking-widest text-gray-600 uppercase">{{ __('Promo code') }}</p>

    <form method="POST" action="{{ route('promo-codes.find') }}" class="-mx-2 sm:mx-0 p-2 md:p-4 bg-white rounded-sm flex flex-wrap items-end gap-2">
        @csrf
        <div>
            <x-label for="promo_code_find" value="{{ __('Have a promo code?') }}" />
            <x-input id="promo_code_find" type="text" name="code" class="mt-1 block w-44 font-mono uppercase" required />
            <x-input-error for="code" class="mt-1" />
        </div>
        <x-secondary-button type="submit">{{ __('Open') }}</x-secondary-button>
    </form>
</section>
