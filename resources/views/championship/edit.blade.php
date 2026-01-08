<x-app-layout>
    <x-slot name="title">
        {{ $championship->title }} - {{ __('Edit championship') }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Edit :championship', ['championship' => $championship->title]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">

        <x-validation-errors class="mb-4" />

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <x-section-title>
                <x-slot name="title">{{ __('Championship details') }}</x-slot>
                <x-slot name="description">
                    
                </x-slot>
            </x-section-title>

            <div class="mt-5 md:mt-0 md:col-span-2">

                <form method="POST" action="{{ route('championships.update', $championship) }}">
                    @method('PUT')
                    @csrf

                    @include('championship.partials.form')

                    <div class="flex items-center justify-end mt-4">
                        <x-button class="ml-4">
                            {{ __('Save') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        <x-section-border />
               
        <div class="md:grid md:grid-cols-3 md:gap-6">
            <x-section-title>
                <x-slot name="title">{{ __('Banner') }}</x-slot>
                <x-slot name="description">
                    {{ __('The championship banner image') }}
                </x-slot>
            </x-section-title>

            <div class="mt-5 md:mt-0 md:col-span-2">

                <div>
                
                    @if ($championship->banner_path)
                        <img src="{{ route('championships.banner.index', $championship) }}" >

                        <form method="POST" action="{{ route('championships.banner.destroy', $championship) }}">
                            @method('DELETE')
                            @csrf

                            <div class="flex items-center justify-end mt-4">
                                <x-danger-button type="submit" class="ml-4">
                                    {{ __('Remove banner') }}
                                </x-danger-button>
                            </div>
                        </form>

                        <x-section-border />
                    @endif
                
                </div>

                <form method="POST" enctype="multipart/form-data" action="{{ route('championships.banner.store', $championship) }}">
                    @csrf

                    <div>
                        <x-label for="banner" value="{{ __('Banner image') }}" />
                        <p class="text-zinc-600 text-sm">{{ __('Image file (jpg, png). Maximum 1200x600 pixels or 10 MB.') }}</p>
                        <x-input-error for="banner" />
                        <x-input id="banner" class="block mt-1 w-full" type="file" name="banner" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-button class="ml-4">
                            {{ __('Save') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        <x-section-border />

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <x-section-title>
                <x-slot name="title">{{ __('Cost and payment') }}</x-slot>
                <x-slot name="description">
                    {{ __('The race registration cost and how to make payments.') }}
                </x-slot>
            </x-section-title>

            <div class="mt-5 md:mt-0 md:col-span-2">
                <form method="POST" action="{{ route('championships.payment.update', $championship) }}">
                    @csrf
                    @method('PUT')

                    <div class="mt-4">
                        <x-label for="registration_price" value="{{ __('Race participation price') }}" />
                        <p class="text-zinc-600 text-sm">{{ __('Insert the cost of each race registration. Use the decimal notation, e.g. for a cost of 80,00 € insert 8000.') }}</p>
                        <x-input id="registration_price" class="block mt-1 w-full" type="number" name="registration_price" :value="old('registration_price', optional($championship ?? null)->registration_price ?? config('races.price'))" />
                    </div>

                    <div class="mt-4">
                        <x-label for="bank" value="{{ __('Bank name') }}" />
                        <x-input id="bank" class="block mt-1 w-full" type="text" name="bank" :value="old('bank', optional($championship->payment ?? null)->bank_name ?? config('races.organizer.bank'))" />
                    </div>

                    <div class="mt-4">
                        <x-label for="bank_account" value="{{ __('Bank account') }}" />
                        <x-input id="bank_account" class="block mt-1 w-full" type="text" name="bank_account" :value="old('bank_account', optional($championship->payment ?? null)->bank_account ?? config('races.organizer.bank_account'))" />
                    </div>

                    <div class="mt-4">
                        <x-label for="bank_holder" value="{{ __('Bank account holder') }}" />
                        <x-input id="bank_holder" class="block mt-1 w-full" type="text" name="bank_holder" :value="old('bank_holder', optional($championship->payment ?? null)->bank_holder ?? config('races.organizer.bank_holder'))" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-button class="ml-4">
                            {{ __('Save') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
        
        <x-section-border />

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <x-section-title>
                <x-slot name="title">{{ __('Bonus') }}</x-slot>
                <x-slot name="description">
                    {{ __('The organizer might issue bonus or discounts for racers. Here you can configure the behavior of each bonus type.') }}
                </x-slot>
            </x-section-title>

            <div class="mt-5 md:mt-0 md:col-span-2">
                <form method="POST" action="{{ route('championships.bonus-settings.update', $championship) }}">
                    @csrf
                    @method('PUT')

                    <div class="mt-4">
                        <x-label for="bonus_mode" value="{{ __('Bonus mode') }}" />
                        <p class="text-zinc-600 text-sm">{{ __('Select how bonuses should be managed for this championship.') }}</p>
                        <select name="bonus_mode" id="bonus_mode" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach (\App\Models\BonusMode::cases() as $mode)
                                <option value="{{ $mode->value }}" @selected(old('bonus_mode', optional($championship ?? null)->bonuses->bonus_mode) === $mode)>
                                    {{ $mode->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-zinc-600 text-sm mt-2">
                            @foreach (\App\Models\BonusMode::cases() as $mode)
                                <span x-show="$el.closest('form').querySelector('#bonus_mode').value == '{{ $mode->value }}'">
                                    {{ $mode->description() }}
                                </span>
                            @endforeach
                        </p>
                        <x-input-error for="bonus_mode" class="mt-2" />
                    </div>

                    <div class="mt-4" x-data="{ mode: '{{ old('bonus_mode', optional($championship ?? null)->bonuses->bonus_mode?->value ?? \App\Models\BonusMode::CREDIT->value) }}' }" x-init="$watch('mode', value => mode = value)">
                        <div x-show="mode == '{{ \App\Models\BonusMode::CREDIT->value }}'">
                            <x-label for="fixed_bonus_amount" value="{{ __('Bonus discount (in Euro)') }}" />
                            <p class="text-zinc-600 text-sm">{{ __('Insert the amount of discount, in Euro, that each bonus grants to the racer. This amount will be deduced from the registration fee. Use the decimal notation, e.g. for a cost of 80,00 € insert 8000.') }}</p>
                            <x-input id="fixed_bonus_amount" class="block mt-1 w-full" type="number" name="fixed_bonus_amount" :value="old('fixed_bonus_amount', optional($championship ?? null)->bonuses->fixed_bonus_amount ?? config('races.bonus_amount'))" />
                            <x-input-error for="fixed_bonus_amount" class="mt-2" />
                        </div>
                    </div>

                    <script>
                        document.getElementById('bonus_mode')?.addEventListener('change', function(e) {
                            const form = e.target.closest('form');
                            const modeElements = form.querySelectorAll('[x-data]');
                            modeElements.forEach(el => {
                                if (el.__x) {
                                    el.__x.$data.mode = e.target.value;
                                }
                            });
                        });
                    </script>

                    <div class="flex items-center justify-end mt-4">
                        <x-button class="ml-4" type="submit">
                            {{ __('Save') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        <x-section-border />

        <livewire:wildcard-settings :championship="$championship" /> 
        
        
        </div>
    </div>
</x-app-layout>
