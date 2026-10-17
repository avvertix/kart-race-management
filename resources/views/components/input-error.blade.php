@props(['for'])

@error($for)
    <p {{ $attributes->merge(['class' => 'text-sm font-medium text-red-500']) }}>{{ $message }}</p>
@enderror
