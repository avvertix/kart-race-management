{{--
    One button that opens the file picker and uploads the payment proof as soon as a file is chosen: the primary action
    of the payment step. $action: where the proof is posted, as `proof`. $replacesRejected: the organizer could not
    confirm the previous proof. $instructions: what the proof must show, right above the button.
--}}
@php
    $replacesRejected = $replacesRejected ?? false;
    $instructions = $instructions ?? null;
@endphp

<div class="max-w-prose space-y-2 text-sm">
    @if ($replacesRejected)
        <p class="rounded-md border border-amber-400 bg-amber-50 px-3 py-2 text-amber-800">{{ __('The organizer could not confirm the previous payment proof. Upload a new one.') }}</p>
    @endif

    @if ($instructions)
        <p class="text-zinc-700">{{ $instructions }}</p>
    @endif

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3" x-data="{ uploading: false }">
        @csrf
        <label class="inline-flex min-h-11 cursor-pointer items-center rounded-md bg-zinc-800 px-4 font-medium text-white hover:bg-zinc-900 focus-within:ring-3 focus-within:ring-zinc-300" :class="uploading && 'pointer-events-none opacity-60'">
            <input type="file" name="proof" accept="image/png,image/jpeg,application/pdf" class="sr-only" aria-label="{{ __('Payment proof') }}" required @change="uploading = true; $el.form.requestSubmit()">
            {{ $replacesRejected ? __('Choose a new payment proof') : __('Choose payment proof') }}
        </label>
        <span x-show="uploading" x-cloak class="text-zinc-600" role="status">{{ __('Uploading…') }}</span>
    </form>
    <p class="text-zinc-500">{{ __('PDF, JPG or PNG, up to 10 MB each.') }}</p>
    <x-input-error for="proof" />
</div>
