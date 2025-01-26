<x-app-layout>
    <x-slot name="title">
        {{ $participant->full_name }} - {{ __('Edit Transponders') }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('races.participants.index', $race) }}">{{ $race->title }}</a></span>
            /
            <span>{{ __('Edit assigned transponder') }}</span>
        </h2>
        <div class="mt-6 bg-white text-xl p-2 space-y-2">
            <p>
                <span class="inline-block font-mono px-2 py-1 rounded bg-orange-100 text-orange-700 print:bg-orange-100 mr-2">{{ $participant->bib }}</span>
                {{ $participant->first_name }} {{ $participant->last_name }}
            </p>
            
            <p>{{ $participant->racingCategory?->name ?? $participant->category }} / {{ $participant->engine }}</p>
            @if ($participant->racingCategory?->tire)
                <p>{{ $participant->racingCategory?->tire->name }}</p>
            @endif
        </div>
    </x-slot>


    <div class="py-3">
        <div class="px-4 sm:px-6 lg:px-8">

            <form method="POST" action="{{ route('transponders.update', $transponder) }}">
                @method('PUT')
                @csrf
                
                    <x-section-title>
                        <x-slot name="title">{{ __('Update transponder') }}</x-slot>
                        <x-slot name="description">
                            
                        </x-slot>
                    </x-section-title>
            
                    <x-validation-errors class="mb-4" />
            
                    <div class="mb-2">
                        <x-label for="transponder" value="{{ __('Transponder') }}*" />
                        <x-input id="transponder" type="text" name="transponder" class="mt-1 block w-full" required autofocus :value="old('transponder', $transponder?->code)" />
                    </div>
                              
                    <div class="">
            
                        <div class="py-5">
                            <x-button class="">
                                {{ __('Update transponder') }}
                            </x-button>
                        </div>
            
                    </div>
            
            </form>

            @can('delete', $transponder)
                
            @endcan
            <x-section-border />

            <form action="{{ route('transponders.destroy', $transponder) }}" method="post">
                @method('DELETE')
                @csrf

                <x-danger-button type="submit">{{ __('Remove transponder') }}</x-danger-button>
            </form>

        </div>
    </div>
</x-app-layout>
