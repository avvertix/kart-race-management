{{-- Placeholder for the pre-filled forms to sign, until the real paperwork is available. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $template->label() }}</title>
</head>
<body>
    <h1>{{ $template->label() }}</h1>
    <p>{{ __('This form is not available yet. Ask the organizer for the paper version.') }}</p>
</body>
</html>
