<div>
    <x-label for="name" value="{{ __('Name') }}" />
    <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', optional($user ?? null)->name)" required autofocus />
    <x-input-error for="name" class="mt-2" />
</div>

<div class="mt-4">
    <x-label for="email" value="{{ __('Email') }}" />
    <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', optional($user ?? null)->email)" required />
    <x-input-error for="email" class="mt-2" />
</div>

<div class="mt-4">
    <x-label for="role" value="{{ __('Role') }}" />
    <flux:select name="role" id="role" class="block mt-1 w-full" required>
        @foreach ($roles as $key => $name)
            <option value="{{ $key }}" @selected(old('role', optional($user ?? null)->role) === $key)>
                {{ $name }}
            </option>
        @endforeach
    </flux:select>
    <x-input-error for="role" class="mt-2" />
</div>
