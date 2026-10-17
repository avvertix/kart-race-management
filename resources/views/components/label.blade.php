@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-zinc-800']) }}>
    {{ $value ?? $slot }}
</label>
