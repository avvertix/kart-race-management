@php
    $selectedProvider = old('licence_provider', $championship?->licences->provider?->value ?? '');
@endphp
<fieldset class="space-y-3">
    <legend class="sr-only">{{ __('Licence provider') }}</legend>
    <label class="flex items-start gap-3">
        <input type="radio" name="licence_provider" value="" @checked($selectedProvider === '') class="mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300">
        <div>
            <span class="text-sm font-medium text-zinc-900">{{ __('Platform default (:provider)', ['provider' => config('races.licence.provider')]) }}</span>
            <p class="text-sm text-zinc-500">{{ __('As for championships created before the provider could be chosen.') }}</p>
        </div>
    </label>
    @foreach (\App\Enums\LicenceProvider::cases() as $provider)
        <label class="flex items-start gap-3">
            <input type="radio" name="licence_provider" value="{{ $provider->value }}" @checked($selectedProvider === $provider->value) class="mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300">
            <span class="text-sm font-medium text-zinc-900">{{ $provider->label() }}</span>
        </label>
    @endforeach
    <x-input-error for="licence_provider" class="mt-2" />
</fieldset>
