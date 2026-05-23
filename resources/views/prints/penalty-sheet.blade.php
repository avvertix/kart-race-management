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
        .race-info { font-size: 12px; color: #444; margin: 2px 0; }
        .category-title { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #222; padding-bottom: 3px; margin-bottom: 7px; }

        .weight-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .weight-table th {
            background-color: #e8e8e8;
            font-weight: bold;
            text-align: center;
            font-size: 12px;
            padding: 4px 3px;
            border: 1px solid #333;
        }
        .weight-table td {
            border: 1px solid #aaa;
            padding: 3px;
            height: 16px;
            font-size: 12px;
        }
        .col-bib { width: 5%; text-align: center; }
        .col-name { width: 35%; }
        .col-category { width: 20%; }
        .col-session { width: 13%; text-align: center; }

        .penalty-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 10px 0 6px 0;
        }
        .penalty-outer { width: 100%; border-collapse: collapse; border: none; }
        .penalty-outer td { border: none; vertical-align: top; padding: 0; }
        .penalty-table { width: 100%; border-collapse: collapse; }
        .penalty-table th {
            background-color: #e8e8e8;
            font-weight: bold;
            text-align: center;
            font-size: 14px;
            padding: 4px 3px;
            border: 1px solid #333;
        }
        .penalty-table td {
            border: 1px solid #aaa;
            padding: 3px;
            height: 22px;
        }
        .penalty-divider { width: 3%; border-right: 2px solid #333; }
        .spacer-left { width: 1%; }
    </style>
</head>
<body>

@foreach ($groups as $group)
<div class="page">

    <div class="header">
        <p class="championship-title">{{ $championship->title }} - {{ $race->title }} &mdash; {{ $race->period }} &mdash; {{ $race->track }}</p>
    </div>

    <div class="category-title">{{ $group['title'] }}</div>

    <table class="weight-table">
        <thead>
            <tr>
                <th width="100px" class="col-bib">N°</th>
                <th width="" class="col-name">PILOTA</th>
                <th width="" class="col-category">CATEGORIA</th>
                <th class="col-session">CRONO</th>
                <th class="col-session">PREFINALE</th>
                <th class="col-session">FINALE</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($group['participants'] as $participant)
                <tr>
                    <td class="col-bib" style="text-align:center;font-weight:bold;">{{ $participant->bib }}</td>
                    <td class="col-name">{{ $participant->full_name }}</td>
                    <td class="col-category">{{ $participant->racingCategory?->short_name ?? $participant->racingCategory?->name }}</td>
                    <td class="col-session"></td>
                    <td class="col-session"></td>
                    <td class="col-session"></td>
                </tr>
            @endforeach
            @for ($i = $group['participants']->count(); $i < $group['minRows']; $i++)
                <tr>
                    <td class="col-bib"></td>
                    <td class="col-name"></td>
                    <td class="col-category"></td>
                    <td class="col-session"></td>
                    <td class="col-session"></td>
                    <td class="col-session"></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="penalty-title">GESTIONE FUORI PESO / PENALITÀ</div>

    <table class="penalty-outer">
        <tr>
            <td style="width:48%;">
                <table class="penalty-table">
                    <thead>
                        <tr>
                            <th style="width:25%;">N° KART</th>
                            <th style="width:25%;">CRONO</th>
                            <th style="width:25%;">PREFINALE</th>
                            <th style="width:25%;">FINALE</th>
                        </tr>
                    </thead>
                    <tbody>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                    </tbody>
                </table>
            </td>
            <td class="penalty-divider">&nbsp;</td>
            <td class="spacer-left">&nbsp;</td>
            <td style="width:48%;">
                <table class="penalty-table">
                    <thead>
                        <tr>
                            <th style="width:25%;">N° KART</th>
                            <th style="width:25%;">CRONO</th>
                            <th style="width:25%;">PREFINALE</th>
                            <th style="width:25%;">FINALE</th>
                        </tr>
                    </thead>
                    <tbody>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

</div>
@if (! $loop->last)
    <div class="page-break"></div>
@endif
@endforeach

</body>
</html>
