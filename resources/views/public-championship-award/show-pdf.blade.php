<!DOCTYPE html>
<html lang="it">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Penalty Sheet</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 0; }

        .page { padding: 0; }
        .page-break { page-break-after: always; }

        .header { text-align: center; margin-bottom: 6px; }
        .banner { width: 100%; max-height: 65px; }
        .championship-title { font-size: 16px; font-weight: bold; margin: 0 0 3px 0; }


        .weight-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }

        .table { border-collapse: collapse; width: 100%; }

        .table th {
            background-color: #e8e8e8;
            font-weight: bold;
            text-align: center;
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
        .col-name { width: 200px }

        .spacer-left { width: 1%; }

        .text-center { text-align: center; }

        .line-through {
            text-decoration: line-through;
            opacity: 0.5;
        }

        .bg-zinc-100 {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>


<div class="page">

    <div class="header">
        <p class="championship-title">{{ $award->name }} - {{ $championship->title }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <td class="">#</td>
                <td class="col-name">{{ __('Name') }}</td>
                <td class="">{{ __('Points') }}</td>
                @foreach($races as $race)
                    <td class="text-center {{ $loop->odd ? 'bg-zinc-100': '' }}">{{ $loop->iteration }}</td>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($ranking as $index => $entry)
                <tr>
                    <td >{{ $index + 1 }}</td>
                    <td class="col-name">{{ $entry['bib'] }}&nbsp;{{ $entry['first_name'] }} {{ $entry['last_name'] }}</td>
                    <td ><strong>{{ $entry['total_points'] }}</strong></td>
                    @foreach($races as $race)
                        @php
                            $racePoints = $entry['points_per_race'][$race->getKey()] ?? null;
                            $isCounted = !isset($entry['counted_race_ids']) || in_array($race->getKey(), $entry['counted_race_ids']);
                        @endphp
                        <td class="text-center {{ $loop->odd ? 'bg-zinc-100': '' }} {{ $racePoints !== null && !$isCounted ? 'line-through' : '' }}">
                            {{ $racePoints !== null ? $racePoints : '-' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 3 + $races->count() }}" class="">
                        {{ __('No participants found') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <ol>

        @foreach($races as $race)
            <li>{{ $race->title }}</li>
        @endforeach
    
    </ol>
    

</div>

</body>
</html>
