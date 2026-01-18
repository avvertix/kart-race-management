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
    <select name="role" id="role" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
        @foreach ($roles as $key => $name)
            <option value="{{ $key }}" @selected(old('role', optional($user ?? null)->role) === $key)>
                {{ $name }}
            </option>
        @endforeach
    </select>
    <x-input-error for="role" class="mt-2" />
</div>
