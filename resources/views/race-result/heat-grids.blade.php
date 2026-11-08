<x-app-layout>
    <x-slot name="title">
        {{ __('Heats and grids') }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

        <div class="flex justify-between items-center mb-4 print:hidden">
            <ol class="text-sm text-zinc-600 max-w-3xl list-decimal pl-5 space-y-1">
                <li>{{ __('Qualifying: every uploaded qualifying file (series or group) is ranked together.') }}</li>
                <li>{{ __('Heats: drivers are split into heats following that ranking. Export each heat for timing.') }}</li>
                <li>{{ __('Pre-final grid: after the heats are run, upload their results (file name containing "Batteria") to get it.') }}</li>
            </ol>
            <div class="flex items-center gap-4">
                @can('create', \App\Models\Transponder::class)
                    <a href="{{ route('races.export.prefinale-grid', $race) }}" class="text-sm text-orange-600 hover:text-orange-900">{{ __('Export pre-final grid for timing') }}</a>
                @endcan
                <a href="{{ route('races.results.index', $race) }}" class="text-sm text-orange-600 hover:text-orange-900">{{ __('Results') }}</a>
            </div>
        </div>

        @can('create', \App\Models\RunResult::class)
            <div class="mb-8 rounded-md bg-white p-4 shadow-sm ring-1 ring-black/5 print:hidden">
                <h3 class="font-semibold text-zinc-800 mb-1">{{ __('Combined results') }}</h3>
                <p class="text-sm text-zinc-500 mb-3">{{ __('Series and heats are never published. Combine them in one result to score and publish it. Combining again replaces the previous one.') }}</p>

                @error('combine')
                    <p class="text-sm text-red-700 mb-3">{{ $message }}</p>
                @enderror

                <div class="flex flex-wrap items-end gap-6">
                    <form method="post" action="{{ route('races.results.combine-qualifying', $race) }}">
                        @csrf
                        <x-secondary-button type="submit">{{ __('Combine qualifying series') }}</x-secondary-button>
                    </form>

                    <form method="post" action="{{ route('races.results.combine-heats', $race) }}" class="flex items-end gap-2">
                        @csrf
                        <label class="text-sm text-zinc-600">
                            {{ __('Classify the heats as') }}
                            <select name="run_type" class="ml-2 rounded-md border-zinc-300 text-sm">
                                @foreach ([\App\Models\RunType::RACE_1, \App\Models\RunType::RACE_2] as $type)
                                    <option value="{{ $type->value }}">{{ $type->localizedName() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <x-secondary-button type="submit">{{ __('Classify heats') }}</x-secondary-button>
                    </form>
                </div>
            </div>
        @endcan

        <form method="get" action="{{ route('races.heat-grids.index', $race) }}">
            @forelse ($grids as $grid)
                @php
                    $ranking = $grid['ranking'];
                @endphp

                <section class="mb-10 break-inside-avoid">
                    <div class="flex items-end justify-between gap-4 mb-2">
                        <h3 class="font-semibold text-xl text-zinc-800">{{ $grid['category'] }}</h3>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 print:hidden">
                            {{ __('Number of heats') }}
                            <input type="number" min="1" max="12" name="heats[{{ $grid['index'] }}]" value="{{ $grid['heats'] }}" class="w-20 rounded-md border-zinc-300 text-sm" />
                        </label>
                    </div>

                    <h4 class="font-semibold text-zinc-700">{{ __('Heats assignment from qualifying') }}</h4>
                    <p class="text-sm text-zinc-500 mb-3">
                        {{ trans_choice(':count qualifying series|:count qualifying series', $ranking['series']) }},
                        {{ trans_choice(':count driver|:count drivers', $ranking['ranked']->count() + $ranking['untimed']->count()) }}.
                        @if ($ranking['method'] === 'single')
                            {{ __('Single qualifying series.') }}
                        @elseif ($ranking['method'] === 'time')
                            {{ __('Several qualifying series: classified by lap time (gap between the two best times :gap%, 1% or less).', ['gap' => number_format($ranking['gap_percent'], 2)]) }}
                        @else
                            {{ __('Several qualifying series: classified by position in the series and then by lap time (gap between the two best times :gap%, more than 1%).', ['gap' => number_format($ranking['gap_percent'], 2)]) }}
                        @endif
                    </p>

                    <div @class(['grid gap-6 mb-6', 'lg:grid-cols-2' => $grid['heats'] === 2, 'lg:grid-cols-3' => $grid['heats'] >= 3])>
                        @foreach ($grid['assignment'] as $heat => $entries)
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h5 class="font-semibold text-zinc-700">{{ __('Heat :number', ['number' => $heat]) }}</h5>
                                    @can('create', \App\Models\Transponder::class)
                                        <a href="{{ route('races.export.heat-grid', ['race' => $race, 'heat' => $heat, 'heats' => $grids->pluck('heats')->all()]) }}" class="text-sm text-orange-600 hover:text-orange-900 print:hidden">{{ __('Export for timing') }}</a>
                                    @endcan
                                </div>
                                <x-table>
                                    <x-slot name="head">
                                        <th scope="col" class="py-2 pl-4 pr-2 text-left text-sm font-semibold text-zinc-900">#</th>
                                        <th scope="col" class="px-2 py-2 text-left text-sm font-semibold text-zinc-900">{{ __('Bib') }}</th>
                                        <th scope="col" class="px-2 py-2 text-left text-sm font-semibold text-zinc-900">{{ __('Name') }}</th>
                                        <th scope="col" class="px-2 py-2 text-right text-sm font-semibold text-zinc-900">{{ __('Qualifying') }}</th>
                                    </x-slot>
                                    @foreach ($entries as $entry)
                                        <tr>
                                            <td class="whitespace-nowrap py-2 pl-4 pr-2 text-sm text-zinc-500">{{ $entry['grid'] }}</td>
                                            <td class="whitespace-nowrap px-2 py-2 text-sm font-mono">{{ $entry['result']->bib }}</td>
                                            <td class="px-2 py-2 text-sm text-zinc-900">{{ $entry['result']->name }}</td>
                                            <td class="whitespace-nowrap px-2 py-2 text-sm text-zinc-500 text-right">
                                                @if ($entry['drawn'])
                                                    {{ __('No time: draw') }}
                                                @else
                                                    {{ $entry['overall'] }}° &middot; {{ $entry['result']->best_lap_time }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </x-table>
                            </div>
                        @endforeach
                    </div>

                    @if ($ranking['excluded']->isNotEmpty())
                        <p class="text-sm text-red-700 mb-4">
                            {{ __('Disqualified in qualifying, not placed:') }}
                            {{ $ranking['excluded']->map(fn ($result) => $result->bib.' '.$result->name)->join(', ') }}
                        </p>
                    @endif

                </section>
            @empty
                <p class="text-zinc-600 p-4">{{ __('No qualifying results uploaded.') }}</p>
            @endforelse

            @if ($grids->isNotEmpty())
                <section class="mb-10">
                    <h3 class="font-semibold text-xl text-zinc-800 mb-1">{{ __('Pre-final grid') }}</h3>
                    <p class="text-sm text-zinc-500 mb-3">
                        {{ __('One grid for all the categories, at most :capacity drivers (:rows rows of two).', ['capacity' => \App\Actions\BuildRaceHeatGrids::TRACK_CAPACITY, 'rows' => \App\Actions\BuildRaceHeatGrids::TRACK_CAPACITY / 2]) }}
                    </p>

                    @if ($prefinale === null)
                        <p class="text-sm text-zinc-500">{{ __('Available once the heats have been run and their results uploaded.') }}</p>
                    @else
                        <x-table>
                            <x-slot name="head">
                                <th scope="col" class="py-2 pl-4 pr-2 text-left text-sm font-semibold text-zinc-900">{{ __('Grid') }}</th>
                                <th scope="col" class="px-2 py-2 text-left text-sm font-semibold text-zinc-900">{{ __('Row') }}</th>
                                <th scope="col" class="px-2 py-2 text-left text-sm font-semibold text-zinc-900">{{ __('Bib') }}</th>
                                <th scope="col" class="px-2 py-2 text-left text-sm font-semibold text-zinc-900">{{ __('Name') }}</th>
                                <th scope="col" class="px-2 py-2 text-left text-sm font-semibold text-zinc-900">{{ __('Category') }}</th>
                                <th scope="col" class="px-2 py-2 text-left text-sm font-semibold text-zinc-900">{{ __('Heat result') }}</th>
                            </x-slot>
                            @foreach ($prefinale['grid'] as $row)
                                <tr>
                                    <td class="whitespace-nowrap py-2 pl-4 pr-2 text-sm font-semibold text-zinc-900">{{ $row['position'] }}</td>
                                    <td class="whitespace-nowrap px-2 py-2 text-sm text-zinc-500">{{ (int) ceil($row['position'] / 2) }}</td>
                                    <td class="whitespace-nowrap px-2 py-2 text-sm font-mono">{{ $row['result']->bib }}</td>
                                    <td class="px-2 py-2 text-sm text-zinc-900">{{ $row['result']->name }}</td>
                                    <td class="whitespace-nowrap px-2 py-2 text-sm text-zinc-500">{{ $row['result']->category }}</td>
                                    <td class="whitespace-nowrap px-2 py-2 text-sm text-zinc-500">
                                        {{ __('Heat :number', ['number' => $row['heat']]) }} &middot; {{ $row['heat_position'] }}°
                                        @if ($row['heat_position'] === 1)
                                            &middot; {{ $row['result']->total_race_time }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </x-table>

                        @if ($prefinale['not_admitted']->isNotEmpty())
                            <p class="text-sm text-red-700 mt-2">
                                {{ __('Beyond the track capacity (:capacity drivers), not admitted:', ['capacity' => \App\Actions\BuildRaceHeatGrids::TRACK_CAPACITY]) }}
                                {{ $prefinale['not_admitted']->map(fn ($row) => $row['position'].'. '.$row['result']->bib.' '.$row['result']->name)->join(', ') }}
                            </p>
                        @endif

                        <p class="text-sm text-zinc-500 mt-2">
                            {{ __('Heats ordered by the winner total time:') }}
                            {{ $prefinale['heats']->map(fn ($heat) => $heat['heat'].'. '.$heat['winner']->name.' ('.$heat['winner']->total_race_time.')')->join(' — ') }}
                        </p>

                        @if ($prefinale['excluded']->isNotEmpty())
                            <p class="text-sm text-red-700 mt-1">
                                {{ __('Did not start or disqualified in heats, not placed:') }}
                                {{ $prefinale['excluded']->map(fn ($result) => $result->bib.' '.$result->name)->join(', ') }}
                            </p>
                        @endif
                    @endif
                </section>
            @endif

            @if ($grids->isNotEmpty())
                <div class="print:hidden">
                    <x-button type="submit">{{ __('Recalculate') }}</x-button>
                </div>
            @endif
        </form>
    </div>
</x-app-layout>
