
<div class="">
    <x-label for="file" value="{{ __('File') }}" />
    <p class="text-sm text-zinc-700">{{ __('Maximum 50 MB. Accepted formats are oxb, zip and 7z archives.') }}</p>
    <x-input-error for="file" />
    <x-input type="file" id="file" name="file" class=" mt-1 block w-full max-w-prose @error('file') is-invalid @enderror" />
</div>

<div class="">
    <x-label for="championship" value="{{ __('Championship') }}" />
    <p class="text-sm text-zinc-700">{{ __('Select the reference championship for this backup, if any.') }}</p>
    <x-input-error for="championship" />
    <select name="championship" id="championship">
        <option value="">{{ __('Select the reference championship') }}</option>
        @foreach ($championships as $championship)
            <option value="{{ $championship->getKey() }}" @selected(old('championship', optional($backup ?? null)->championship?->getKey()) === $championship->getKey()) >{{ $championship->title }}</option>
        @endforeach
    </select>
</div>
