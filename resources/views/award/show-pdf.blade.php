<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $award->name }}</title>
    <style>
        @page { margin: 34mm 10mm 12mm 10mm; }

        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 0; padding: 0; color: #18181b; }

        header { position: fixed; top: -28mm; left: 0; right: 0; height: 24mm; border-bottom: 2px solid #18181b; }
        header .award-type { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #c2410c; margin: 0; }
        header .award-name { font-size: 18px; font-weight: bold; margin: 1mm 0 0 0; }
        header .award-scope { font-size: 13px; font-weight: bold; margin: 1mm 0 0 0; }
        header .award-details { font-size: 9px; color: #52525b; margin: 1mm 0 0 0; }

        footer { position: fixed; bottom: -8mm; left: 0; right: 0; height: 6mm; font-size: 8px; color: #71717a; }
        footer .page-number:after { content: counter(page); }
        footer .right { float: right; }

        .table { border-collapse: collapse; width: 100%; }
        .table th {
            background-color: #e8e8e8;
            font-weight: bold;
            font-size: 9px;
            padding: 3px;
            border: 1px solid #333;
        }
        .table td { border-bottom: 1px solid #aaa; padding: 3px; font-size: 10px; vertical-align: top; }
        .table tr { page-break-inside: avoid; }
        .table .group-heading td { background-color: #f4f4f5; font-weight: bold; padding-top: 6px; border-bottom: 1px solid #333; }

        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .nowrap { white-space: nowrap; }
        .bg-zinc-100 { background-color: #f4f4f5; }
        .dimmed { color: #71717a; }
        .line-through { text-decoration: line-through; color: #a1a1aa; }
        .block { display: block; }
        .text-xs { font-size: 8px; }
        .text-zinc-500 { color: #71717a; }

        .races { margin-top: 6mm; font-size: 9px; }
        .races ol { margin: 1mm 0 0 0; padding-left: 5mm; }
    </style>
</head>
<body>

<header>
    <p class="award-type">{{ $award->type->localizedName() }}</p>
    <p class="award-name">{{ $award->name }}</p>
    @php
        $scope = $award->isCategoryAward() ? $award->category?->name : $award->categories->pluck('name')->join(', ');
    @endphp
    @if(filled($scope) && mb_strtolower(trim($scope)) !== mb_strtolower(trim($award->name)))
        <p class="award-scope">{{ $scope }}</p>
    @endif
    <p class="award-details">
        {{ $championship->title }}
        &middot; {{ __('Ranking mode') }}: {{ $award->ranking_mode->localizedName() }}@if($award->ranking_mode === \App\Models\AwardRankingMode::BestN) ({{ $award->best_n }}@if($award->requiresMinimumRaces()), {{ __('minimum to qualify') }}@endif)@endif
        &middot; {{ __('Wildcard filter') }}: {{ $award->wildcard_filter->localizedName() }}
        &middot; {{ $award->isPublished() ? __('Public') : __('Private') }}
    </p>
</header>

<footer>
    <span class="right page-number">{{ __('Page') }} </span>
    {{ $award->name }} &middot; {{ now()->setTimezone(config('races.timezone'))->format('d/m/Y H:i') }}
</footer>

<main>
    @php
        $columns = 3 + $races->count() + ($award->isDayAward() ? 2 : 0);
    @endphp

    <table class="table">
        <thead>
            <tr>
                <th class="text-left">#</th>
                <th class="text-left">{{ __('Name') }}</th>
                <th class="text-right">{{ __('Points') }}</th>
                @if($award->isDayAward())
                    <th class="text-center">{{ __('Session bonus') }}</th>
                    <th class="text-left">{{ __('Tie-break') }}</th>
                @endif
                @foreach($races as $race)
                    <th class="text-center {{ $loop->odd ? 'bg-zinc-100' : '' }}">{{ $loop->iteration }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($ranking as $index => $entry)
                @if(! ($entry['qualified'] ?? true) && ($ranking->get($index - 1)['qualified'] ?? true))
                    <tr class="group-heading">
                        <td colspan="{{ $columns }}">{{ trans_choice('Less than :count race|Less than :count races', $award->best_n, ['count' => $award->best_n]) }}</td>
                    </tr>
                @endif
                <tr class="{{ ($entry['qualified'] ?? true) ? '' : 'dimmed' }}">
                    <td>{{ $index + 1 }}</td>
                    <td class="nowrap">{{ $entry['bib'] }}&nbsp;&nbsp;{{ $entry['first_name'] }} {{ $entry['last_name'] }}</td>
                    <td class="text-right nowrap">
                        <strong>{{ $entry['total_points'] }}</strong>
                        @if($award->ranking_mode === \App\Models\AwardRankingMode::BestN)
                            <span class="block text-xs text-zinc-500">{{ trans_choice(':value race|:value races', count($entry['points_per_race']), ['value' => count($entry['points_per_race'])]) }}</span>
                        @endif
                    </td>
                    @if($award->isDayAward())
                        <td class="text-center">{{ $entry['session_bonus'] }}</td>
                        <td>@include('award.partials.tie-breaks', ['tieBreaks' => $entry['tie_breaks']])</td>
                    @endif
                    @foreach($races as $race)
                        @php
                            $racePoints = $entry['points_per_race'][$race->getKey()] ?? null;
                            $isCounted = ! isset($entry['counted_race_ids']) || in_array($race->getKey(), $entry['counted_race_ids']);
                        @endphp
                        <td class="text-center {{ $loop->odd ? 'bg-zinc-100' : '' }} {{ $racePoints !== null && ! $isCounted ? 'line-through' : '' }}">
                            {{ $racePoints !== null ? $racePoints : '-' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columns }}" class="text-center">{{ __('No participants found') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($races->isNotEmpty())
        <div class="races">
            <strong>{{ __('Races') }}</strong>
            <ol>
                @foreach($races as $race)
                    <li>{{ $race->title }}</li>
                @endforeach
            </ol>
        </div>
    @endif
</main>

</body>
</html>
