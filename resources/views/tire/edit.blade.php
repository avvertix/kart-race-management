<x-app-layout>
    <x-slot name="title">
        {{ $participant->full_name }} - {{ __('Edit tire') }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('races.participants.index', $race) }}">{{ $race->title }}</a></span>
            /
            <span>{{ __('Edit tire') }}</span>
        </h2>
        <div class="mt-6 bg-white text-xl p-2 space-y-2">
            <p>
                <span class="inline-block font-mono px-2 py-1 rounded bg-orange-100 text-orange-700 print:bg-orange-100 mr-2">{{ $participant->bib }}</span>
                {{ $participant->first_name }} {{ $participant->last_name }}
            </p>
            
            <p>{{ $participant->categoryConfiguration()?->name ?? $participant->category }} / {{ $participant->engine }}</p>
            <p>{{ $participant->categoryConfiguration()?->tire()->name }}</p>
        </div>
    </x-slot>


    <div class="py-3">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <form method="POST" action="{{ route('tires.update', $tire) }}">
            @csrf
            @method('PUT')
        
            <x-section-title>
                <x-slot name="title">{{ __('Change an assigned tire') }}</x-slot>
                <x-slot name="description">
                    
                </x-slot>
            </x-section-title>

            <x-validation-errors class="mb-4" />

                <div class="mb-2">
                    <x-label for="tire" value="{{ __('Tire code') }}*" />
                    <x-input id="tire" type="text" name="tire" :value="old('tire', $tire->code)" class="mt-1 block w-full" required autofocus />
                </div>
                      
            <div class="">

                <div class="py-5">
                    <x-button class="">
                        {{ __('Update tire') }}
                    </x-button>
                </div>

            </div>

</form>
        </div>
    </div>
</x-app-layout>
