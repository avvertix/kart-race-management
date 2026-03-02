<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ $award->name }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="actions">
        @can('update', $award)
            <x-button-link href="{{ route('awards.edit', $award) }}">
                {{ __('Edit') }}
            </x-button-link>
        @endcan
        @can('delete', $award)
            <form action="{{ route('awards.destroy', $award) }}" method="post">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:border-red-700 focus:ring focus:ring-red-200 transition">
                    {{ __('Delete') }}
                </button>
            </form>
        @endcan
    </x-slot>

    <div class="mb-6">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-3">
            <div>
                <dt class="text-sm font-medium text-zinc-500">{{ __('Type') }}</dt>
                <dd class="mt-1 text-sm text-zinc-900">{{ $award->type->localizedName() }}</dd>
            </div>

            @if($award->isCategoryAward())
                <div>
                    <dt class="text-sm font-medium text-zinc-500">{{ __('Category') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900">{{ $award->category?->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500">{{ __('Ranking mode') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900">
                        {{ $award->ranking_mode->localizedName() }}
                        @if($award->ranking_mode === \App\Models\AwardRankingMode::BestN)
                            ({{ $award->best_n }})
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500">{{ __('Wildcard filter') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900">{{ $award->wildcard_filter->localizedName() }}</dd>
                </div>
            @else
                <div>
                    <dt class="text-sm font-medium text-zinc-500">{{ __('Categories') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900">{{ $award->categories->pluck('name')->join(', ') }}</dd>
                </div>
            @endif

            @if($award->ranking_mode === \App\Models\AwardRankingMode::SpecificRaces)
                <div>
                    <dt class="text-sm font-medium text-zinc-500">{{ __('Races') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900">{{ $award->races->pluck('title')->join(', ') }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="">
        <x-table>
            <x-slot name="head" class="">
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">#</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Name') }}</th>
                <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-zinc-900">{{ __('Points') }}</th>
                @foreach($races as $race)
                    <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-zinc-900 max-w-12 odd:bg-zinc-100"><span class="block w-full truncate">{{ $race->title }}</span></th>
                @endforeach
            </x-slot>

            @forelse ($ranking as $index => $entry)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-zinc-500 sm:pl-6">{{ $index + 1 }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-zinc-900"><span class="inline-block font-mono font-normal mr-2 w-7">{{ $entry['bib'] }}</span>{{ $entry['first_name'] }} {{ $entry['last_name'] }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-900 text-right font-semibold">{{ $entry['total_points'] }}</td>
                    @foreach($races as $race)
                        @php
                            $racePoints = $entry['points_per_race'][$race->getKey()] ?? null;
                            $isCounted = !isset($entry['counted_race_ids']) || in_array($race->getKey(), $entry['counted_race_ids']);
                        @endphp
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-center odd:bg-zinc-100 {{ $racePoints !== null && !$isCounted ? ' line-through' : 'text-zinc-900' }}">
                            {{ $racePoints !== null ? $racePoints : '-' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $award->isCategoryAward() ? 5 + $races->count() : 5 }}" class="px-3 py-4 text-center text-sm text-zinc-500">
                        {{ __('No participants found') }}
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>

</x-championship-page-layout>
