{{-- Placeholder for the pre-filled forms to sign, until the real paperwork is available. It lists the details the form will carry. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $document->label() }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        dl { margin: 0 0 16px; }
        dt { font-weight: bold; margin-top: 6px; }
        dd { margin: 0; }
        .signature { margin-top: 48px; border-top: 1px solid #000; width: 60%; padding-top: 4px; }
    </style>
</head>
<body>
    <h1>{{ $document->label() }}</h1>
    <p>{{ __('This form is not available yet. Ask the organizer for the paper version.') }}</p>

    @foreach (array_filter([$holder ?? null, ...($parents ?? [])]) as $index => $person)
        <h2>{{ $index === 0 ? __('Holder') : __('Parent or guardian :number', ['number' => $index]) }}</h2>
        <dl>
            <dt>{{ __('Name') }}</dt>
            <dd>{{ $person->fullName() }}</dd>
            <dt>{{ __('Birth') }}</dt>
            <dd>{{ $person->birth_date }} &middot; {{ $person->birth_place }}</dd>
            @if ($person->fiscal_code)
                <dt>{{ __('Fiscal code') }}</dt>
                <dd>{{ $person->fiscal_code }}</dd>
            @endif
            <dt>{{ __('Residence address') }}</dt>
            <dd>{{ $person->residence_address }}</dd>
        </dl>
    @endforeach

    <p class="signature">{{ __('Signature') }}</p>
</body>
</html>
