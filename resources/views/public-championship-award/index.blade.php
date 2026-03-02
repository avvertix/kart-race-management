<x-app-layout>
    <x-slot name="title">
        {{ __('Awards') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ $championship->title }}
        </h2>
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

        <h3 class="text-lg font-bold mb-4">{{ __('Awards') }}</h3>

        @forelse ($groupedAwards as $typeName => $awards)
            <h4 class="font-semibold text-sm text-zinc-700 mt-8 mb-3">{{ $typeName }}</h4>

            @foreach ($awards as $award)
                <div class="mb-8">
                    <h5 class="font-semibold text-base mb-3">
                        <a class="underline" href="{{ route('public.awards.show', $award) }}">{{ $award->name }}</a>
                    </h5>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr>
                                    <td class="px-2 text-xs w-8">#</td>
                                    <td class="px-2 text-xs">{{ __('Name') }}</td>
                                    <td class="px-2 text-xs text-right">{{ __('Points') }}</td>
                                    @foreach($races as $race)
                                        <td class="px-2 text-xs text-center max-w-12 odd:bg-zinc-100"><span class="block w-full truncate">{{ $race->title }}</span></td>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rankingsPerAward[$award->getKey()] as $index => $entry)
                                    <tr>
                                        <td class="px-2 py-2 border-b text-zinc-500">{{ $index + 1 }}</td>
                                        <td class="px-2 py-2 border-b font-medium"><span class="inline-block font-mono font-normal mr-2 w-7">{{ $entry['bib'] }}</span>{{ $entry['first_name'] }} {{ $entry['last_name'] }}</td>
                                        <td class="px-2 py-2 border-b text-right font-semibold">{{ $entry['total_points'] }}</td>
                                        @foreach($races as $race)
                                            @php
                                                $racePoints = $entry['points_per_race'][$race->getKey()] ?? null;
                                                $isCounted = !isset($entry['counted_race_ids']) || in_array($race->getKey(), $entry['counted_race_ids']);
                                            @endphp
                                            <td class="px-2 py-2 border-b text-center odd:bg-zinc-100 {{ $racePoints !== null && !$isCounted ? 'line-through text-zinc-400' : 'text-zinc-900' }}">
                                                {{ $racePoints !== null ? $racePoints : '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 3 + $races->count() }}" class="px-2 py-4 text-center text-sm text-zinc-500">
                                            {{ __('No participants found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @empty
            <p class="text-zinc-600 p-4">{{ __('No awards.') }}</p>
        @endforelse
    </div>
</x-app-layout>
