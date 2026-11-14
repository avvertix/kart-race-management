{{--
    The parents' self-declaration authorising the licence of an underage holder (art. 46 D.P.R. 445/2000), in Italian as
    the paperwork it replaces. Pre-filled with the holder and up to two parents entered; what is missing is left blank to
    fill in by hand. The organizer of the instance takes the place of the federation.
--}}
@php
    /** @var ?\App\Data\HolderData $holder */
    /** @var array<int, \App\Data\HolderData> $parents */
    $organizer = config('races.organizer');
    $date = fn (?string $value) => $value ? \Carbon\CarbonImmutable::parse($value)->format('d/m/Y') : null;
    $contact = $parents[0] ?? null;
    $licenceType = $licenceRequest->licenceTypeLabel();
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>{{ $document->label() }}</title>
    <style>
        @page { margin: 18mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.5; }
        .organizer { text-align: center; margin-bottom: 18px; }
        .organizer strong { font-size: 13px; text-transform: uppercase; }
        h1 { text-align: center; font-size: 13px; margin: 0; }
        .law { text-align: center; margin: 2px 0 18px; }
        h2 { text-align: center; font-size: 12px; margin: 14px 0 6px; }
        p { margin: 6px 0; }
        table.line { width: 100%; border-collapse: collapse; margin: 4px 0; }
        table.line td { padding: 6px 8px 1px; width: 1%; white-space: nowrap; vertical-align: bottom; }
        table.line td:first-child { padding-left: 0; }
        table.line td:last-child { padding-right: 0; }
        table.line td.fill { border-bottom: 1px solid #000; width: auto; white-space: normal; padding: 6px 4px 1px; }
        ul { margin: 4px 0; padding-left: 16px; }
        .signatures { margin-top: 28px; }
        .note { margin-top: 24px; font-style: italic; }
    </style>
</head>
<body>
    <div class="organizer">
        <strong>{{ $organizer['name'] }}</strong>
        @if (filled($organizer['address']))
            <br>{{ $organizer['address'] }}
        @endif
        @if (filled($organizer['vat']))
            <br>P.IVA {{ $organizer['vat'] }}
        @endif
    </div>

    <h1>AUTOCERTIFICAZIONE DELLA QUALITÀ DI<br>GENITORE – ESERCENTE LA POTESTÀ GENITORIALE</h1>
    <p class="law">Art. 46 D.P.R. n. 445 del 28 dicembre 2000</p>

    <p>Il/I sottoscritto/i</p>
    @foreach ([0, 1] as $index)
        @php($parent = $parents[$index] ?? null)
        <table class="line"><tr>
            <td class="fill" style="width: 40%">{{ $parent?->fullName() }}</td>
            <td>nato a</td>
            <td class="fill" style="width: 30%">{{ $parent?->birth_place }}</td>
            <td>il</td>
            <td class="fill" style="width: 15%">{{ $date($parent?->birth_date) }}</td>
        </tr></table>
    @endforeach

    <p>Sotto la loro personale responsabilità ed a piena conoscenza della responsabilità penale prevista per le dichiarazioni false dall’art. 76 del D.P.R. 445/2000 e dalle disposizioni del Codice Penale e dalle leggi speciali in materia</p>

    <h2>DICHIARANO</h2>

    <p>di esercitare la potestà genitoriale/tutela sul minore</p>
    <table class="line"><tr>
        <td class="fill" style="width: 40%">{{ $holder?->fullName() }}</td>
        <td>nato a</td>
        <td class="fill" style="width: 30%">{{ $holder?->birth_place }}</td>
        <td>il</td>
        <td class="fill" style="width: 15%">{{ $date($holder?->birth_date) }}</td>
    </tr></table>
    <p>residente a</p>
    <table class="line"><tr>
        <td>cap</td>
        <td class="fill" style="width: 10%">{{ $holder?->residence_address->postal_code }}</td>
        <td>città</td>
        <td class="fill" style="width: 25%">{{ $holder?->residence_address->city }}</td>
        <td>prov</td>
        <td class="fill" style="width: 8%">{{ $holder?->residence_address->province }}</td>
        <td>indirizzo</td>
        <td class="fill" style="width: 40%">{{ $holder?->residence_address->address }}</td>
    </tr></table>

    <p>e di voler ricevere le comunicazioni ai seguenti recapiti:</p>
    <table class="line"><tr>
        <td>tel/cell</td>
        <td class="fill" style="width: 40%">{{ $contact?->phone }}</td>
        <td>e-mail</td>
        <td class="fill" style="width: 50%">{{ $contact?->email }}</td>
    </tr></table>
    <p>indirizzo di postalizzazione c/o</p>
    <table class="line"><tr>
        <td>cap</td>
        <td class="fill" style="width: 10%"></td>
        <td>città</td>
        <td class="fill" style="width: 25%"></td>
        <td>prov</td>
        <td class="fill" style="width: 8%"></td>
        <td>indirizzo</td>
        <td class="fill" style="width: 40%"></td>
    </tr></table>

    <h2>AUTORIZZANO</h2>

    <p>{{ $organizer['name'] }}</p>
    <ul>
        <li>a richiedere per il minore predetto una licenza{{ $licenceType ? " {$licenceType}" : '' }} per la partecipazione alle gare;</li>
        <li>l’utilizzo, la pubblicazione e/o diffusione in qualsiasi forma delle fotografie del minore a titolo gratuito, senza limiti di tempo per scopi pubblicitari, promozionali e divulgativi;</li>
        <li>la conservazione delle foto del minore nei propri archivi informatici.</li>
    </ul>

    <div class="signatures">
        @foreach ([0, 1] as $index)
            <table class="line"><tr>
                <td>Firma esercente la potestà/tutela{{ isset($parents[$index]) ? ' ('.$parents[$index]->fullName().')' : '' }}</td>
                <td class="fill"></td>
            </tr></table>
        @endforeach
    </div>

    <p class="note">Allegare copia dei documenti di identità degli esercenti la potestà/tutela sul minore.</p>
</body>
</html>
