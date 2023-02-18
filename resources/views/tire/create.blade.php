<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('races.participants.index', $race) }}">{{ $race->title }}</a></span>
            /
            <span>{{ __('Assign tire set') }}</span>
        </h2>
        <div class="mt-6 bg-white text-xl p-2 space-y-2">
            <p>
                <span class="inline-block font-mono px-2 py-1 rounded bg-orange-100 text-orange-700 print:bg-orange-100 mr-2">{{ $participant->bib }}</span>
                {{ $participant->first_name }} {{ $participant->last_name }}
            </p>
            
            <p>{{ $participant->category()?->name ?? $participant->category }} / {{ $participant->engine }}</p>
            <p>{{ $participant->category()?->tire()->name }}</p>
        </div>
    </x-slot>


    <div class="py-3">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <form method="POST" action="{{ route('participants.tires.store', $participant) }}">
        @csrf
        
            <x-jet-section-title>
                <x-slot name="title">{{ __('Assign a tire set') }}</x-slot>
                <x-slot name="description">
                    
                </x-slot>
            </x-jet-section-title>

            <x-jet-validation-errors class="mb-4" />


            @for ($i = 0; $i < 5; $i++)
                
                <div class="mb-2">
                    <x-jet-label for="tire_{{ $i }}" value="{{ __('Tire :number', ['number' => $i+1]) }}*" />
                    <x-jet-input id="tire_{{ $i }}" type="text" name="tires[]" class="mt-1 block w-full" required autofocus />
                </div>
            @endfor
                      
            <div class="">

                <div class="py-5">
                    <x-jet-button class="">
                        {{ __('Assign tires') }}
                    </x-jet-button>
                </div>

            </div>

</form>
        </div>
    </div>
</x-app-layout>
