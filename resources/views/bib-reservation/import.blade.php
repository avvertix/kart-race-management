<x-app-layout>
    <x-slot name="title">
        {{ __('Import race number reservations') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('championships.bib-reservations.index', $championship) }}">{{ $championship->title }}</a></span>
            <span>/</span>
            <span>{{ __('Import race number reservations') }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">

            <form method="POST" action="{{ route('championships.bib-reservations.import.store', $championship) }}">
                @csrf

                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <x-section-title>
                        <x-slot name="title">{{ __('Import multiple reservations') }}</x-slot>
                        <x-slot name="description">
                            <p class="mt-1 text-sm text-zinc-600">{{ __('Specify each reservation on its own line.') }}</p>
                            <p class="mt-1 text-sm text-zinc-600">{{ __('Supported format is:') }}</p>
                            <p class="mt-1 text-sm text-zinc-600"><code>bib;driver_name;driver_licence;contact_email;expiration_date</code></p>
                            <p class="mt-1 text-sm text-zinc-600">{{ __('contact_email and expiration_date are optional. Leave them empty if not needed.') }}</p>
                            <p class="mt-1 text-sm text-zinc-600">{{ __('Dates must be in YYYY-MM-DD format and must be after today.') }}</p>
                            <p class="mt-1 text-sm text-zinc-600">{{ __('For example:') }}</p>
                            <p class="mt-1 text-sm text-zinc-600"><code>1;Mario Rossi;DRV-0001;mario@example.com;2026-12-31</code></p>
                            <p class="mt-1 text-sm text-zinc-600"><code>2;Luigi Bianchi;DRV-0002;;</code></p>
                        </x-slot>
                    </x-section-title>

                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="px-4 py-5">
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="reservations" value="{{ __('Reservations to import') }}*" />
                                    <x-textarea id="reservations" name="reservations" class="mt-1 block w-full" rows="10" required autofocus>{{ old('reservations') }}</x-textarea>
                                    <x-input-error for="reservations" class="mt-2" />
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
                                {{ __('Import reservations') }}
                            </x-button>
                        </div>
                    </div>
                </div>

            </form>

        </div>
    </div>
</x-app-layout>
