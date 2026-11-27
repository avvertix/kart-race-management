<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ __('Issue promo codes') }} - {{ $championship->title }}
    </x-slot>

    <x-validation-errors class="mb-4" />

    <form method="POST" action="{{ route('championships.promo-codes.store', $championship) }}">
        @csrf

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <x-section-title>
                <x-slot name="title">{{ __('Issue promo codes') }}</x-slot>
                <x-slot name="description">
                    {{ __('Codes work only in this championship and don\'t expire. Send each code\'s link to its winner or customer.') }}
                </x-slot>
            </x-section-title>

            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="px-4 py-5 space-y-4">
                    <fieldset class="space-y-2">
                        <legend class="text-sm font-medium text-zinc-900">{{ __('What the code gives') }}</legend>
                        @foreach (\App\Enums\EntitlementMode::cases() as $mode)
                            <label class="flex items-start gap-3">
                                <input type="radio" name="mode" value="{{ $mode->value }}" @checked(old('mode', \App\Enums\EntitlementMode::Credit->value) === $mode->value) class="mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300">
                                <span class="text-sm text-zinc-900">{{ $mode->label() }}</span>
                            </label>
                        @endforeach
                        <x-input-error for="mode" class="mt-2" />
                    </fieldset>

                    <div>
                        <x-label for="amount" value="{{ __('Amount') }}" />
                        <p class="text-sm text-zinc-600">{{ __('The number of race fees, or the balance in euros, e.g. 50.00.') }}</p>
                        <x-input id="amount" type="text" inputmode="decimal" name="amount" class="mt-1 block w-40" :value="old('amount', 1)" required />
                        <x-input-error for="amount" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="count" value="{{ __('How many codes') }}" />
                        <x-input id="count" type="number" min="1" max="200" name="count" class="mt-1 block w-40" :value="old('count', 1)" required />
                        <x-input-error for="count" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="code" value="{{ __('Custom code (optional)') }}" />
                        <p class="text-sm text-zinc-600">{{ __('Only when issuing a single code. Letters, digits and dashes; leave empty for a random code.') }}</p>
                        <x-input id="code" type="text" name="code" class="mt-1 block w-60 font-mono uppercase" :value="old('code')" maxlength="20" />
                        <x-input-error for="code" class="mt-2" />
                    </div>

                    <x-button type="submit">{{ __('Issue promo codes') }}</x-button>
                </div>
            </div>
        </div>
    </form>
</x-championship-page-layout>
