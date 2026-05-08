<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Penalty template') }}</x-slot>
        <x-slot name="description">{{ __('Saved penalties help quickly compile penalty messages during a race.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <div class="px-4 py-5 space-y-4">
            <div>
                <x-label for="title" value="{{ __('Title') }}" />
                <x-input id="title" type="text" name="title" class="mt-1 block w-full" :value="old('title', $penalty->title ?? '')" required autofocus maxlength="250" />
                <x-input-error for="title" class="mt-2" />
            </div>

            <div>
                <x-label for="description" value="{{ __('Description') }}" />
                <x-textarea id="description" name="description" class="mt-1 block w-full" rows="4" maxlength="2000">{{ old('description', $penalty->description ?? '') }}</x-textarea>
                <p class="mt-1 text-xs text-zinc-500">{{ __('This text will be pre-filled when the template is selected while composing a race message.') }}</p>
                <x-input-error for="description" class="mt-2" />
            </div>
        </div>
    </div>
</div>
