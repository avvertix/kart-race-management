<x-app-layout>
    <x-slot name="title">
        {{ $race->title }} - {{ __('Import transponders') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

        <form method="POST" action="{{ route('races.transponders.import.store', $race) }}">
            @csrf

            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-section-title>
                    <x-slot name="title">{{ __('Import multiple transponders') }}</x-slot>
                    <x-slot name="description">
                        <p class="mt-1 text-sm text-zinc-600">{{ __('Specify each transponder assignment on its own line.') }}</p>
                        <p class="mt-1 text-sm text-zinc-600">{{ __('Supported format is:') }}</p>
                        <p class="mt-1 text-sm text-zinc-600"><code>racer_hash;transponder_code</code></p>
                        <p class="mt-1 text-sm text-zinc-600">{{ __('For example:') }}</p>
                        <p class="mt-1 text-sm text-zinc-600"><code>AB123456;1234567</code></p>
                        <p class="mt-1 text-sm text-zinc-600"><code>CD789012;9876543</code></p>
                    </x-slot>
                </x-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    <div class="px-4 py-5">
                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-6 sm:col-span-4">
                                <x-label for="transponders" value="{{ __('Transponders to import') }}*" />
                                <x-textarea id="transponders" name="transponders" class="mt-1 block w-full" rows="10" required autofocus>{{ old('transponders') }}</x-textarea>
                                <x-input-error for="transponders" class="mt-2" />
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
                            {{ __('Import transponders') }}
                        </x-button>
                    </div>
                </div>
            </div>
        </form>

    </div>
</x-app-layout>
