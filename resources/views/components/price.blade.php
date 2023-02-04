@props(['currency' => \App\Models\Currency::EUR])

<span {{ $attributes->merge(['class' => 'tabular-nums font-mono']) }}>
    {{ $currency->format($slot) }}
</span>
