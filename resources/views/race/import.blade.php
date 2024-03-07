<x-app-layout>
    <x-slot name="title">
        {{ __('Import races') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('championships.show', $championship) }}">{{ $championship->title }}</a></span>
            <span>/</span>
            <span>{{ __('Import races') }}</span>
        </h2>
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

<form method="POST" action="{{ route('championships.races.import.store', $championship) }}">
@csrf
        
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-section-title>
                    <x-slot name="title">{{ __('Create multiple races') }}</x-slot>
                    <x-slot name="description">
                        <p class="mt-1 text-sm text-zinc-600">{{ __('Specify each race on its own line.') }}</p>
                        <p class="mt-1 text-sm text-zinc-600">{{ __('Supported format is:') }}</p>
                        <p class="mt-1 text-sm text-zinc-600"><code>start_date;end_date;title;track;description;</code></p>
                        <p class="mt-1 text-sm text-zinc-600">{{ __('Where the dates are specified in YYYY-MM-DD format.') }}</p>
                        <p class="mt-1 text-sm text-zinc-600">{{ __('For example:') }}</p>
                        <p class="mt-1 text-sm text-zinc-600"><code>2023-03-05;2023-03-05;Race title;Race Track;Additional description;</code></p>
                    </x-slot>
                </x-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                        <div class="px-4 py-5">
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="races" value="{{ __('Races to import') }}*" />
                                    <x-textarea id="races" type="date" name="races" class="mt-1 block w-full" rows="10" required autofocus>{{ old('races') }}</x-textarea>
                                    <x-input-error for="races" class="mt-2" />
                                </div>
                            </div>
                        </div>
                </div>
            </div>
            
            <div class="md:grid md:grid-cols-3 md:gap-6">
                
                <div></div>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                        <div class="px-4 py-5">
                            <x-button class="">
                                {{ __('Import races') }}
                            </x-button>
                        </div>
                </div>
            </div>

</form>
        </div>
    </div>
</x-app-layout>
