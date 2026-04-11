<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ __('Import bonuses') }} - {{ $championship->title }}
    </x-slot>

    <form method="POST" action="{{ route('championships.bonuses.import.store', $championship) }}">
        @csrf

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <x-section-title>
                <x-slot name="title">{{ __('Import multiple bonuses') }}</x-slot>
                <x-slot name="description">
                    <p class="mt-1 text-sm text-zinc-600">{{ __('Specify each bonus on its own line.') }}</p>
                    <p class="mt-1 text-sm text-zinc-600">{{ __('Supported format is:') }}</p>
                    <p class="mt-1 text-sm text-zinc-600"><code>driver_name;driver_licence;driver_fiscal_code;bonus_type;amount</code></p>
                    <p class="mt-1 text-sm text-zinc-600">{{ __('Either driver_licence or driver_fiscal_code must be provided. Leave the other empty.') }}</p>
                    <p class="mt-1 text-sm text-zinc-600">{{ __('Supported bonus types:') }}</p>
                    @foreach (\App\Models\BonusType::cases() as $type)
                        <p class="mt-1 text-sm text-zinc-600"><code>{{ $type->value }}</code> — {{ $type->localizedName() }}</p>
                    @endforeach
                    <p class="mt-1 text-sm text-zinc-600">{{ __('For example:') }}</p>
                    <p class="mt-1 text-sm text-zinc-600"><code>Mario Rossi;DRV-0001;;{{ \App\Models\BonusType::REGISTRATION_FEE->value }};2</code></p>
                    <p class="mt-1 text-sm text-zinc-600"><code>Mario Bianchi;;RSSM-----------U;{{ \App\Models\BonusType::REGISTRATION_FEE->value }};1</code></p>
                </x-slot>
            </x-section-title>

            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="px-4 py-5">
                    <div class="grid grid-cols-6 gap-6">
                        <div class="col-span-6 sm:col-span-4">
                            <x-label for="bonuses" value="{{ __('Bonuses to import') }}*" />
                            <x-textarea id="bonuses" name="bonuses" class="mt-1 block w-full" rows="10" required autofocus>{{ old('bonuses') }}</x-textarea>
                            <x-input-error for="bonuses" class="mt-2" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <div></div>

            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="px-4 py-5">
                    <x-button>
                        {{ __('Import bonuses') }}
                    </x-button>
                </div>
            </div>
        </div>

    </form>

</x-championship-page-layout>
