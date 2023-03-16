<form method="POST" class="" enctype="multipart/form-data" action="{{ url()->signedRoute('payment-verification.store', $participant->signedUrlParameters()) }}">
    @csrf

    <input type="hidden" name="participant" value="{{ $participant->uuid }}">

    <x-jet-input-error for="proof" class="mb-2" />

    <input type="file" class="block mb-1" name="proof" id="proof" accept="image/png,image/jpeg,application/pdf">
    <p class="text-sm" class="mb-2">{{ __('Accept pdf file or png/jpg images (maximum size 10 MB)') }}</p>

    <p class="">
        <x-jet-button type="submit">
            {{ __('Submit payment receipt') }}
        </x-jet-button>
    </p>
</form>