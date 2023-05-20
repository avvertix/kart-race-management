<x-app-layout>
    <x-slot name="header">
        @include('championship.partials.heading')
    </x-slot>


    <div class="pt-0 pb-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <h3 class="text-xl mb-6">{{ $uniqueParticipantsCount }} {{ __('participants') }}</h3>

        <x-table>
            <x-slot name="head">
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6  w-4/12">{{ __('Participant') }} ▼</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900 w-1/12">{{ __('Races') }}</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900 w-6/12">{{ __('Participation history') }}</th>
            </x-slot>

            @forelse ($participants as $participant)

                <tr>
                    <td class="py-4 pl-4 pr-3 text-zinc-900 sm:pl-6">

                        <a href="{{ route('participants.show', $participant) }}"
                            class=" hover:text-orange-900  font-medium group">
                            <span class="font-mono text-lg inline-block bg-gray-100 group-hover:bg-orange-200 px-2 py-1 rounded mr-2">{{ $participant->bib }}</span>
                            {{ $participant->full_name }}
                        </a>
                        <span class="font-mono font-bold">{{ $participant->driver['licence_number'] }}</span>
                    </td>
                    <td class="px-3 py-4 text-sm text-zinc-500">{{ $participant->participationHistory?->count() ?? '-' }}</td>
                    <td class="px-3 py-4 text-sm text-zinc-500 flex gap-4 overflow-x-auto">
                        @foreach ($participant->participationHistory as $item)
                            <div class=" flex-shrink-0 basis-2/6 p-2 rounded {{ $item->full_name !== $participant->full_name || $item->bib !== $participant->bib  ? 'border border-red-500 bg-red-50' : '' }}">
                                <p class="font-bold">
                                    <a class="underline" href="{{ route('participants.show', $item) }}" target="_blank">{{ $item->race->title }}</a>
                                </p>
                                <p><span class="font-mono">{{ $item->bib }}</span></p>
                                <p>{{ $item->category()?->name ?? $item->category }}</p>
                                <p>{{ $item->full_name }}</p>
                                <p><span class="font-mono font-bold">{{ $item->driver['licence_number'] }}</span></p>
                            </div>
                        @endforeach
                    </td>

                </tr>

                
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-4 space-y-2 text-center">
                        <p>{{ __('No races at the moment') }}</p>
                        @can('create', \App\Model\Race::class)
                            <p>
                                <x-button-link href="{{ route('championships.races.create', $championship) }}">
                                    {{ __('Create a race') }}
                                </x-button-link>
                            </p>
                        @endcan
                    </td>
                </tr>
            @endforelse
        </x-table>
        
        </div>
    </div>
</x-app-layout>
