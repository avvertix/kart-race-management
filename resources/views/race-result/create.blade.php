<x-app-layout>
    <x-slot name="title">
        {{ __('Upload results') }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">
        <div class="p-4 bg-white rounded max-w-xl">

            <h3 class="mb-4">{{ __('Upload result files') }}</h3>

            <form action="{{ route('races.results.store', $race) }}" enctype="multipart/form-data" method="POST" class="flex flex-col gap-3">
                @csrf

                <div>
                    <x-label for="files" value="{{ __('XML result files') }}" />
                    <input id="files" type="file" name="files[]" multiple accept=".xml" class="mt-1 block w-full" />
                    <x-input-error for="files" class="mt-2" />
                    <x-input-error for="files.*" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-button type="submit">
                        {{ __('Upload results') }}
                    </x-button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
