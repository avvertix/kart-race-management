<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ __('Bonus settings') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="actions">
        @can('update', $championship)
            <x-button-link href="{{ route('championships.bonuses.index', $championship) }}">
                {{ __('View bonuses') }}
            </x-button-link>
        @endcan
    </x-slot>

    <x-validation-errors class="mb-4" />

    <div class="md:grid md:grid-cols-3 md:gap-6">
        <x-section-title>
            <x-slot name="title">{{ __('Bonus settings') }}</x-slot>
            <x-slot name="description">
                {{ __('The organizer might issue bonus or discounts for racers. Here you can configure the behavior of each bonus type.') }}
            </x-slot>
        </x-section-title>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <form method="POST" action="{{ route('championships.bonus-settings.update', $championship) }}" x-data="{mode: '{{ old('bonus_mode', optional($championship ?? null)->bonuses->bonus_mode?->value ?? \App\Models\BonusMode::CREDIT->value) }}' }">
                @csrf
                @method('PUT')

                <div class="mt-4">
                    <x-label for="bonus_mode" value="{{ __('Bonus mode') }}" />
                    <p class="text-zinc-600 text-sm">{{ __('Select how bonuses should be managed for this championship.') }}</p>
                    <select name="bonus_mode" id="bonus_mode" x-model="mode" class="block mt-1 border-zinc-300 focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50 rounded-md shadow-sm">
                        @foreach (\App\Models\BonusMode::cases() as $mode)
                            <option value="{{ $mode->value }}" @selected(old('bonus_mode', optional($championship ?? null)->bonuses->bonus_mode) === $mode)>
                                {{ $mode->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error for="bonus_mode" class="mt-2" />
                    <ul class="text-zinc-600 text-sm mt-2">
                        @foreach (\App\Models\BonusMode::cases() as $mode)
                            <li>
                               <span class="font-bold">{{ $mode->localizedName() }}</span>. {{ $mode->description() }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="mt-4" x-show="mode == '{{ \App\Models\BonusMode::CREDIT->value }}'">
                    <x-label for="fixed_bonus_amount" value="{{ __('Credit value (in Euro)') }}" />
                    <p class="text-zinc-600 text-sm">{{ __('Insert the amount of discount, in Euro, that each credit grants to the racer. This amount will be deduced from the registration fee. Use the decimal notation, e.g. for a cost of 80,00 € insert 8000.') }}</p>
                    <x-input id="fixed_bonus_amount" class="block mt-1 w-full" type="number" name="fixed_bonus_amount" :value="old('fixed_bonus_amount', optional($championship ?? null)->bonuses->fixed_bonus_amount ?? config('races.bonus_amount'))" />
                    <x-input-error for="fixed_bonus_amount" class="mt-2" />
                </div>

                <div class="flex items-center justify-end mt-4">
                    <x-button class="ml-4" type="submit">
                        {{ __('Save') }}
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</x-championship-page-layout>
