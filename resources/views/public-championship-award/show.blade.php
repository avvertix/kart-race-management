<x-app-layout>
    <x-slot name="title">
        {{ $award->name }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ $championship->title }}
        </h2>
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

        <div class="mb-4">
            <a class="text-sm underline" href="{{ route('public.championships.awards.index', $championship) }}">{{ __('Back to awards') }}</a>
            <h3 class="text-lg font-bold">{{ $award->name }}</h3>
            <p class="text-sm text-zinc-500">{{ $award->type->localizedName() }}</p>
        </div>

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
                    @forelse ($ranking as $index => $entry)
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
</x-app-layout>
