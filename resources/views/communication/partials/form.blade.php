<div class="">
    <x-label for="message" value="{{ __('Message (maximum 300 characters)') }}" />
    <x-input-error for="message" />
    <x-textarea id="message" name="message" class=" mt-1 block w-full max-w-prose @error('message') is-invalid @enderror" rows="2" cols="50">{{ old('message', optional($communication ?? null)->message) }}</x-textarea>
</div>


<div class="">
    <x-label for="starts_at" value="{{ __('Show date') }}" />
    <x-input-error for="starts_at" />
    <x-input type="date" id="starts_at" name="starts_at" class=" mt-1 block w-full max-w-prose @error('starts_at') is-invalid @enderror" value="{{ old('starts_at', optional($communication ?? null)->starts_at?->toDateString() ?? today()->toDateString()) }}" />
</div>

<div class="">
    <x-label for="ends_at" value="{{ __('Expiration date') }}" />
    <x-input-error for="ends_at" />
    <x-input type="date" id="ends_at" name="ends_at" class=" mt-1 block w-full max-w-prose @error('ends_at') is-invalid @enderror" value="{{ old('ends_at', optional($communication ?? null)->ends_at?->toDateString()) }}" />
</div>

<div class="">
    <x-label for="target_user_role" value="{{ __('Users to which the message is presented (participants only by default)') }}" />
    <x-input-error for="target_user_role" />

    @php
        $oldTargetUser = collect(old('target_user_role', optional($communication ?? null)->target_user_role));
    @endphp

    <div class="flex items-center gap-4">
       
        <label class="flex items-center">
            <x-checkbox name="target_user_role[]" id="target_user_role-anonim" value="anonim" :checked="$oldTargetUser->contains('anonim') || $oldTargetUser->isEmpty()" />
            <span class="ml-2 text-sm text-zinc-600">{{ __('Participants') }}</span>
        </label>

        @foreach (\Laravel\Jetstream\Jetstream::$roles as $key => $role)
            <label class="flex items-center">
                <x-checkbox name="target_user_role[]" id="target_user_role-{{ $key }}" value="{{ $key }}" :checked="$oldTargetUser->contains($key)" />
                <span class="ml-2 text-sm text-zinc-600">{{ $role->name }}</span>
            </label>
        @endforeach
    </div>

</div>

<div class="">
    <x-label for="theme" value="{{ __('Graphic theme') }}" />
    <x-input-error for="theme" />
    <select id="theme" name="theme" class=" mt-1 block w-full max-w-prose @error('theme') is-invalid @enderror">
        <option value="info" @selected(old('theme', optional($communication ?? null)->theme) === 'info')>{{ __('Informative') }}</option>
    </select>
</div>
