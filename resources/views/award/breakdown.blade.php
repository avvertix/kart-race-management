<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ $participant->first_name }} {{ $participant->last_name }} - {{ $award->name }} - {{ $championship->title }}
    </x-slot>
    <div class="mb-6">
        <a href="{{ route('awards.show', $award) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-orange-600 hover:text-orange-900">
            <x-ri-arrow-left-line class="size-4 shrink-0" />
            {{ $award->name }}
        </a>

        <h3 class="text-lg font-bold mt-1">
            <span class="inline-block font-mono font-normal mr-2">{{ $participant->bib }}</span>{{ $participant->first_name }} {{ $participant->last_name }}
        </h3>

        <dl class="grid grid-cols-2 gap-x-4 gap-y-4 sm:grid-cols-4 mt-4">
            <div>
                <dt class="text-sm font-medium text-zinc-500">{{ __('Total points') }}</dt>
                <dd class="mt-1 text-lg font-semibold text-zinc-900">{{ $breakdown['total_points'] }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-zinc-500">{{ __('Races counted') }}</dt>
                <dd class="mt-1 text-sm text-zinc-900">{{ $breakdown['races_counted'] }}</dd>
            </div>
        </dl>
    </div>

    <div class="space-y-8">
        @forelse ($races as $race)
            @php
                $entries = $breakdown['entries_by_race']->get($race->getKey(), collect());
                $racePoints = $breakdown['points_per_race'][$race->getKey()] ?? null;
                $isCounted = !isset($breakdown['counted_race_ids']) || in_array($race->getKey(), $breakdown['counted_race_ids']);
            @endphp

            @if($entries->isNotEmpty())
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <a href="{{ route('races.results.index', $race) }}" class="font-semibold text-sm text-orange-600 hover:text-orange-900">{{ $race->title }}</a>
                        @if($racePoints !== null && !$isCounted)
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-500">{{ __('Not counted') }}</span>
                        @endif
                        <span class="text-sm font-semibold {{ $racePoints !== null && !$isCounted ? 'text-zinc-400 line-through' : 'text-zinc-900' }}">
                            {{ __('Total') }}: {{ $racePoints ?? 0 }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr>
                                    <td class="px-2 py-1 text-xs text-zinc-500 w-3/6">{{ __('Session') }}</td>
                                    <td class="px-2 py-1 text-xs text-zinc-500 w-1/6">{{ __('Category') }}</td>
                                    <td class="px-2 py-1 text-xs text-zinc-500 w-1/6">{{ __('Result') }}</td>
                                    <td class="px-2 py-1 text-xs text-zinc-500 w-1/6 text-right">{{ __('Points') }}</td>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entries as $entry)
                                    <tr>
                                        <td class="px-2 py-2 border-b">{{ $entry['run_title'] }} <span class="text-zinc-400">({{ $entry['run_type']->localizedName() }})</span></td>
                                        <td class="px-2 py-2 border-b text-zinc-500">{{ $entry['category_label'] }}</td>
                                        <td class="px-2 py-2 border-b">
                                            @if($entry['is_dnf'] || $entry['is_dns'] || $entry['is_dq'])
                                                {{ $entry['status']->localizedName() }}
                                            @else
                                                {{ $entry['position'] }}
                                            @endif
                                        </td>
                                        <td class="px-2 py-2 border-b text-right font-medium">{{ $entry['points'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @empty
        @endforelse

        @if($breakdown['entries_by_race']->isEmpty())
            <p class="text-zinc-600">{{ __('No results found for this participant.') }}</p>
        @endif
    </div>

</x-championship-page-layout>
