<x-app-layout>
    <x-slot name="title">
        {{ __('Participants') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        @include('championship.partials.heading')
    </x-slot>


    <div class="pt-0 pb-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <p class="mb-6">{{ $uniqueParticipantsCount }} {{ __('participants') }}</p>

    
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <div class="min-w-full divide-y divide-zinc-300 bg-white">

                    <div class="grid grid-cols-12 bg-zinc-50">
                        <div scope="col" class="py-3 px-3 text-left text-sm font-semibold text-zinc-900 col-span-4">{{ __('Participant') }} ▼</div>
                        <div scope="col" class="px-2 py-3 text-left text-sm font-semibold text-zinc-900 col-span-8">{{ __('Participation history') }}</div>
                    </div>

                    @forelse ($participants as $participant)

                        <div class="grid grid-cols-12">

                        <div class="py-3 px-3 text-zinc-900  col-span-4">

                            <div class="flex flex-row gap-1">
                                <div>
                                    <p class="min-w-10 text-right font-mono text-lg bg-gray-100 group-hover:bg-orange-200 px-2 py-0.5 rounded">
                                        {{ $participant->bib }}
                                    </p>
                                </div>
                                <div>
                                    <p class="font-medium py-0.5 leading-7">
                                        {{ $participant->full_name }}
                                    </p>
                                    <p><span class="font-mono font-bold">{{ $participant->driver['licence_number'] }}</span></p>
                                    <p class="text-sm">
                                        {{$participant->participationHistory?->count() }} {{ __('races') }}, last

                                        <a class="text-sm underline" href="{{ route('participants.show', $participant) }}" target="_blank">{{ $participant->participationHistory?->first()->race->title }}</a>

                                    </p>
                                </div>
                            </div>

                        </div>

                            <div class="grid-cols-subgrid px-2 py-3 text-sm text-zinc-500 flex gap-4 overflow-x-auto col-span-8">
                                @foreach ($participant->participationHistory as $item)
                                    <div class=" flex-shrink-0 w-72 p-2 rounded {{ $item->full_name !== $participant->full_name || $item->bib !== $participant->bib  ? 'border border-red-500 bg-red-50' : '' }}">
                                        <p class="flex flex-col">
                                            <span class="block text-sm">{{ $item->race->period }}</span>
                                            <a class="font-bold underline" href="{{ route('participants.show', $item) }}" target="_blank">{{ $item->race->title }}</a>
                                        </p>
                                        <p>{{ $item->racingCategory?->name ?? __('no category') }}</p>
                                        @if ($item->full_name !== $participant->full_name || $item->bib !== $participant->bib)
                                            <p><span class="font-mono">{{ $item->bib }}</span></p>
                                            <p>{{ $item->full_name }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>


                        </div>

                    @empty

                        <div colspan="5" class="px-3 py-6 text-center ">
                            <p>{{ __('No participants at the moment.') }}</p>
                        </div>


                    @endforelse

                </div>
            </div>
        
        </div>
    </div>
</x-app-layout>
