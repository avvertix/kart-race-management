@props(['value', 'timezone' => config('app.timezone')])

<time {{ $attributes->merge(['class' => 'tabular-nums', 'datetime' => $value->toIso8601String()]) }}>
    @if (($slot ?? false) && !empty(trim($slot)))
        {{ $slot }}
    @else
        {{ $value->locale(app()->currentLocale())->setTimezone($timezone)->isoFormat('l HH:mm') }}
    @endif
</time>
