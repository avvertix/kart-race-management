<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>

    <style>
    .page-break {
        page-break-after: always;
    }
    body { font-family: DejaVu Sans; }
    </style>

</head>
<body>

@foreach ($participants as $participant)

    <div style="padding:80px">

        <h1>{{ __('Receipt') }}</h1>

        <p>{{ ($race->event_end_at ?? $race->event_start_at)->locale(app()->currentLocale())->setTimezone($race->timezone)->isoFormat('D MMMM YYYY') }}</p>

        <p>{{ $race->title }}</p>


        <div>
            <div style="width:100%;height:1px;background-color:#d1d5db"></div>
        </div>

        <p>
            {{ config('races.organizer.name') }}<br/>
            {{ config('races.organizer.address') }}<br/>
            {{ config('races.organizer.vat') }}
        </p>
        

        <div>
            <div style="width:100%;height:1px;background-color:#d1d5db"></div>
        </div>

        <p>{{ __('Bill to') }}</p>

        <p>{{ str("{$participant->first_name} {$participant->last_name}")->title() }}<br/>
            {{ str($participant->driver['fiscal_code'] ?? '')->upper() }}<br/>
            {{ __('Birth :place on :date', [
                'place' => $participant->driver['birth_place'],
                'date' => $participant->driver['birth_date'],
            ]) }}<br/>
            {{ __(':address :city :province :postal_code', [
                'address' => $participant->driver['residence_address']['address'] ?? null,
                'city' => $participant->driver['residence_address']['city'] ?? null,
                'postal_code' => $participant->driver['residence_address']['postal_code'] ?? null,
                'province' => $participant->driver['residence_address']['province'] ?? null,
            ]) }}
        </p>


        <table style="width: 100%; border-collapse: collapse;" border="0" cellpadding="2">
            <tr>
                <td>{{ __('Description') }}</td>
                <td>{{ __('Total') }}</td>
            </tr>

            <tr>
                <td>{{ __('Sport activity contribution') }}</td>
                <td><x-price>{{ $participant->price()->last() }}</x-price></td>
            </tr>
        </table>

        <p>
            {{ $participant->payment_channel?->localizedName() }}
        </p>
        
    </div>

    <div class="page-break"></div>
@endforeach

</body>
</html>
