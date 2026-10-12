<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'KRM') : config('app.name', 'KRM') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">

@vite(['resources/css/app.css', 'resources/js/app.js'])
