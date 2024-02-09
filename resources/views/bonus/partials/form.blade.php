<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Details') }}</x-slot>
        <x-slot name="description">{{ __('Add details about the bonus type and quantity for a specific driver.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
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
                    <x-label for="amount" value="{{ __('Amount') }}" />
                    <p class="text-zinc-600 text-sm">{{ __('The total amount of bonus/discount that can use during the championship.') }}</p>
                    <x-input id="amount" type="number" required min="1" name="amount" :value="old('amount', optional($bonus ?? null)->amount)" class="mt-1 block w-full" autocomplete="amount" />
                    <x-input-error for="amount" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="bonus_type" value="{{ __('Bonus type') }}" />
                    <select name="bonus_type" id="bonus_type">
                        <option value="" disabled>{{ __('Select the type of bonus') }}</option>
                        @foreach (\App\Models\BonusType::cases() as $bonusType)
                            <option value="{{ $bonusType->value }}" @selected(old('bonus_type', optional($bonus ?? null)->bonus_type) === $bonusType) >{{ $bonusType->localizedName() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="bonus_type" class="mt-2" />
                </div>
            </div>
        </div>
    </div>
</div>
