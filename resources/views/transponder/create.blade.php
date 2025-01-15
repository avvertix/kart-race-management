<x-app-layout>
    <x-slot name="title">
        {{ $participant->full_name }} - {{ __('Assign transponder') }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('races.participants.index', $race) }}">{{ $race->title }}</a></span>
            /
            <span>{{ __('Assign transponder') }}</span>
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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        @include('transponder.partials.form')
        </div>
    </div>
</x-app-layout>
