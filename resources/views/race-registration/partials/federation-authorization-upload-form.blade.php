<form method="POST" class="" enctype="multipart/form-data" action="{{ url()->signedRoute('federation-authorization.store', $participant->signedUrlParameters()) }}">
    @csrf

    <input type="hidden" name="participant" value="{{ $participant->uuid }}">

    <x-input-error for="authorization" class="mb-2" />

    <flux:input type="file" class="block mb-1" name="authorization" id="authorization" accept="image/png,image/jpeg,application/pdf" />

    <p class="text-sm mb-2">{{ __('Accept pdf file or png/jpg images (maximum size 10 MB)') }}</p>

    <p class="">
        <x-button type="submit">
            {{ __('Submit authorization') }}
        </x-button>
    </p>
</form>
