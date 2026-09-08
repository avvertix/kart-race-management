<!DOCTYPE html>
<html lang="it">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $runResult->title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 0; }

        .page { padding: 0; }

        .header { text-align: center; margin-bottom: 6px; }
        .championship-title { font-size: 16px; font-weight: bold; margin: 0 0 3px 0; }
        .run-result-title { font-size: 13px; margin: 0; }

        .table { border-collapse: collapse; width: 100%; }

        .table th {
            background-color: #e8e8e8;
            font-weight: bold;
            text-align: left;
            font-size: 12px;
            padding: 4px 3px;
            border: 1px solid #333;
        }
        .table td {
            border-bottom: 1px solid #aaa;
            padding: 3px;
            height: 16px;
            font-size: 12px;
        }

        .text-right { text-align: right; }
        .text-muted { color: #777; }

        .badge {
            display: inline-block;
            background-color: #fdf2f8;
            color: #be185d;
            border: 1px solid #f9a8d4;
            border-radius: 3px;
            padding: 0 3px;
            font-size: 10px;
        }
    </style>
</head>
<body>

<div class="page">

    <div class="header">
        <p class="championship-title">{{ $championship->title }} &mdash; {{ $race->title }}</p>
        <p class="run-result-title">{{ $runResult->title }} &middot; {{ $runResult->run_type->localizedName() }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>{{ __('Pos.') }}</th>
                <th>{{ __('Bib') }}</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Category') }}</th>
                @if ($runResult->run_type->isRace())
                    <th>{{ __('Total time') }}</th>
                    <th>{{ __('Laps') }}</th>
                    <th>{{ __('Gap') }}</th>
                    <th>{{ __('Interval') }}</th>
                    <th>{{ __('Best lap') }}</th>
                @endif
                @if ($runResult->run_type->isQualify())
                    <th>{{ __('Best lap') }}</th>
                    <th>{{ __('Gap') }}</th>
                    <th>{{ __('Interval') }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($participantResults as $participantResult)
                <tr>
                    <td>
                        @if ($participantResult->is_dnf || $participantResult->is_dns || $participantResult->is_dq)
                            <span class="text-muted">{{ $participantResult->status->localizedName() }}</span>
                        @else
                            {{ $participantResult->position }}
                        @endif
                    </td>
                    <td><strong>{{ $participantResult->bib }}</strong></td>
                    <td>
                        {{ $participantResult->name }}
                        @if ($race->isNationalOrInternational() && $participantResult->participant?->isOutOfZone())
                            <span class="badge">
                                {{ filled($participantResult->participant->region) ? __('OZ :region', ['region' => $participantResult->participant->region_and_nationality]) : __('OZ') }}
                            </span>
                        @endif
                    </td>
                    <td>{{ $participantResult->category }}</td>
                    @if ($runResult->run_type->isRace())
                        <td>{{ $participantResult->total_race_time }}</td>
                        <td>{{ $participantResult->laps }}</td>
                        <td>{{ $participantResult->gap_from_leader }}</td>
                        <td>{{ $participantResult->gap_from_previous }}</td>
                        <td>{{ $participantResult->best_lap_time }}</td>
                    @endif
                    @if ($runResult->run_type->isQualify())
                        <td>{{ $participantResult->best_lap_time }}</td>
                        <td>{{ $participantResult->gap_from_leader }}</td>
                        <td>{{ $participantResult->gap_from_previous }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $runResult->run_type->isRace() ? 9 : ($runResult->run_type->isQualify() ? 7 : 4) }}">
                        {{ __('No participant results.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>

</body>
</html>
