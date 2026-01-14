<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Participant bonus')}}</x-slot>
        <x-slot name="description">{{ __('Add details about the bonus type and quantity for a specific driver.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    @livewire('driver-search')
                </div>
                
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver" value="{{ __('Driver Name') }}*" />
                    <x-input id="driver" type="text" name="driver" :value="old('driver', optional($bonus ?? null)->driver)" class="mt-1 block w-full" required autocomplete="driver" />
                    <x-input-error for="driver" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_licence" value="{{ __('Driver Licence Number') }}*" />
                    <x-input id="driver_licence" type="text" name="driver_licence" :value="old('driver_licence', optional($bonus ?? null)->driver_licence)" required class="mt-1 block w-full" autocomplete="driver_licence" />
                    <x-input-error for="driver_licence" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="bonus_type" value="{{ __('Bonus type') }}" />
                    <select name="bonus_type" id="bonus_type" class="block mt-1 border-zinc-300 focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50 rounded-md shadow-sm">
                        <option value="" disabled>{{ __('Select the type of bonus') }}</option>
                        @foreach (\App\Models\BonusType::cases() as $bonusType)
                            <option value="{{ $bonusType->value }}" @selected(old('bonus_type', optional($bonus ?? null)->bonus_type) === $bonusType) >{{ $bonusType->localizedName() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="bonus_type" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    @if(($bonus_mode ?? \App\Models\BonusMode::CREDIT) === \App\Models\BonusMode::CREDIT)
                        <x-label for="amount" value="{{ __('Amount') }}" />
                        <p class="text-zinc-600 text-sm">{{ __('The total amount of bonuses usable during the championship.') }}</p>

                        <p class="flex items-center gap-4" x-data="{ amount: {{ old('amount', optional($bonus ?? null)->amount ?? 1) }},  fixed_bonus_amount: {{ $fixed_bonus_amount }} }">
                            <x-input id="amount" type="number" required min="1" x-model="amount" name="amount" :value="old('amount', optional($bonus ?? null)->amount ?? 1)" class="mt-1 block max-w-36" autocomplete="amount" />

                            <span class="shrink-0">&times;</span>
                            <span class="shrink-0 tabular-nums">{{ $fixed_bonus_amount / 100 }} {{ __('€/bonus') }}</span>
                            <span class="shrink-0">=</span>
                            <span class="shrink-0 tabular-nums" x-text="(fixed_bonus_amount*amount)/100 + ' €'">{{ $fixed_bonus_amount }}</span>
                        </p>

                        <x-input-error for="amount" class="mt-2" />
                    @else
                        <x-label for="amount" value="{{ __('Total balance (in Euro)') }}" />
                        <p class="text-zinc-600 text-sm">{{ __('Insert the total monetary balance available for this bonus. Use the decimal notation, e.g. for a balance of 80,00 € insert 8000.') }}</p>
                        <x-input id="amount" type="number" required min="1" name="amount" :value="old('amount', optional($bonus ?? null)->amount)" class="mt-1 block w-full" autocomplete="amount" />
                        <x-input-error for="amount" class="mt-2" />
                    @endif
                </div>
                
            </div>

            <script>
                document.addEventListener('livewire:init', () => {
                    Livewire.on('driver-selected', (event) => {
                        const data = event[0];

                        // Fill the form fields
                        document.getElementById('driver').value = data.driver;
                        document.getElementById('driver_licence').value = data.licence;
                    });
                });
            </script>
        </div>
    </div>
</div>
