<x-app-layout>
    <x-slot name="title">
        {{ $race->title }} - {{ __('Import communications') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

        <form method="POST" action="{{ route('races.communications.import.store', $race) }}">
            @csrf

            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-section-title>
                    <x-slot name="title">{{ __('Import multiple messages') }}</x-slot>
                    <x-slot name="description">
                        <p class="mt-1 text-sm text-zinc-600">{{ __('Specify each message on its own line.') }}</p>
                        <p class="mt-1 text-sm text-zinc-600">{{ __('Supported format is:') }}</p>
                        <p class="mt-1 text-sm text-zinc-600"><code>type;session;message</code></p>
                        <p class="mt-1 text-sm text-zinc-600">{{ __('type: communication or penalty') }}</p>
                        <p class="mt-1 text-sm text-zinc-600">{{ __('session: warm up, qualify, race 1, race 2 (or leave empty)') }}</p>
                        <p class="mt-1 text-sm text-zinc-600">{{ __('For example:') }}</p>
                        <p class="mt-1 text-sm text-zinc-600"><code>communication;;Race briefing at 09:00 in the main tent.</code></p>
                        <p class="mt-1 text-sm text-zinc-600"><code>penalty;race 1;Kart 5: 10 second penalty for causing a collision.</code></p>
                    </x-slot>
                </x-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    <div class="px-4 py-5">
                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-6 sm:col-span-4">
                                <x-label for="communications" value="{{ __('Messages to import') }}*" />
                                <x-textarea id="communications" name="communications" class="mt-1 block w-full" rows="10" required autofocus>{{ old('communications') }}</x-textarea>
                                <x-input-error for="communications" class="mt-2" />
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
                            {{ __('Import messages') }}
                        </x-button>
                    </div>
                </div>
            </div>
        </form>

    </div>
</x-app-layout>
