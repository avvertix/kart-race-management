<x-app-layout>
    <x-slot name="title">
        {{ $participant->full_name }} - {{ __('Transponders') }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-4xl text-zinc-800 leading-tight flex gap-2">
            <span class="print:hidden"><a href="{{ route('races.participants.index', $race) }}">{{ $race->title }}</a></span>
            <span class="print:hidden">/</span>
            <span>{{ __('Assigned transponders') }}</span>
        </h2>
        <div class="prose prose-zinc mb-6">
            <p class="">{{ __('At the end of the race, before the prize awards, you need to return the transponder to the organizer or the time keeping service.') }}</p>
        </div>
        <div class="mt-6 flex gap-4">

            <div class="bg-white text-xl p-2 print:p-0 space-y-2 grow basis-1/2">
                <p class="text-xl font-bold">
                    <span class="inline-block font-mono px-2 py-1 rounded bg-zinc-100 text-zinc-900 print:bg-zinc-100 mr-2">{{ $participant->bib }}</span>
                    {{ $participant->first_name }} {{ $participant->last_name }}
                </p>
                
                <p>{{ $participant->racingCategory?->name ?? $participant->category }} / {{ $participant->engine }}</p>
                @if ($participant->racingCategory?->tire)
                    <p>{{ $participant->racingCategory?->tire->name }}</p>
                @endif
            </div>
            
            <div class="bg-white text-xl p-2 print:p-0 space-y-2 grow basis-1/2">
                <p class="text-xl">
                    {{ $race->title }} 
                </p>
                <div class="mt-2 flex items-center text-sm text-zinc-500">
                    <div>
                        <svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    {{ $race->period }}

                    <div class="ml-4">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mr-1.5 h-5 w-5 flex-shrink-0 text-zinc-400">
                            <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    {{ $race->track }}
                </div>
                <p class="text-base font-light">{{ $race->championship->title }}</p>
            </div>

        </div>
    </x-slot>


    <div class="py-3">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <h3 class="text-xl font-bold mb-1">{{ __('Transponders') }}</h3>

            @foreach ($transponders as $item)
                <div class="mb-4">
                    <p><span class="font-mono text-3xl">{{ $item->code }}</span> <a class="text-orange-600 hover:text-orange-900" href="{{ route('transponders.edit', $item) }}">{{ __('edit') }}</a></p>
                </div>
            @endforeach

        </div>
    </div>

    @can('create', \App\Model\Transponder::class)
        <div class="py-3">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                @if ($transponderLimit > 0)
                    @include('transponder.partials.form')
                @endif
            </div>
        </div>
    @endcan
</x-app-layout>
