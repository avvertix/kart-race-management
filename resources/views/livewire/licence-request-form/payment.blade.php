@use('App\Enums\LicenceRequestPaymentStatus')

<dl class="grid grid-cols-3 gap-x-4 gap-y-2 text-sm">
    <dt class="text-zinc-500">{{ __('Amount') }}</dt>
    <dd class="col-span-2 font-mono tabular-nums">
        {{ $licenceRequest->amountLabel() ?? '—' }}
    </dd>
    <dt class="text-zinc-500">{{ __('Status') }}</dt>
    <dd class="col-span-2">{{ $licenceRequest->payment_status->label() }}</dd>
</dl>

@switch ($licenceRequest->payment_status)
    @case (LicenceRequestPaymentStatus::AwaitingPrice)
        <p class="text-sm text-zinc-600">{{ __('The organizer sets the amount after checking your request. You can pay once it is set.') }}</p>
        @break

    @case (LicenceRequestPaymentStatus::AwaitingPayment)
        <div class="space-y-2 text-sm">
            <p>{{ __('Pay by bank transfer to:') }}</p>
            <p class="rounded-sm border border-zinc-200 bg-zinc-50 p-2">
                {{ config('races.organizer.bank_holder') }}
                <br>{{ config('races.organizer.bank') }}
                <br><span class="font-mono select-all break-all">{{ config('races.organizer.bank_account') }}</span>
            </p>
            <p>{{ __('Use the following text as the bank transfer reason:') }} <span class="font-mono select-all break-all">{{ $licenceRequest->ulid }}</span></p>

            @if ($licenceRequest->latestPayment)
                <p class="rounded-md border border-amber-400 bg-amber-50 px-2 py-1 text-amber-700">{{ __('The organizer could not confirm the previous payment proof. Upload a new one.') }}</p>
            @endif

            <div class="flex flex-wrap items-center gap-2">
                <input type="file" wire:model="proof" accept="image/png,image/jpeg,application/pdf" class="text-sm max-w-full" aria-label="{{ __('Payment proof') }}">
                <x-secondary-button type="button" wire:click="uploadProof" wire:loading.attr="disabled" wire:target="proof,uploadProof">{{ __('Upload payment proof') }}</x-secondary-button>
            </div>
            <x-input-error for="proof" />
        </div>
        @break

    @case (LicenceRequestPaymentStatus::PaymentSubmitted)
        <p class="text-sm text-zinc-600">{{ __('Payment proof uploaded. The organizer will confirm it.') }}</p>
        @break

    @case (LicenceRequestPaymentStatus::Paid)
        <p class="text-sm text-lime-700">{{ __('Payment confirmed by the organizer.') }}</p>
        @break

    @case (LicenceRequestPaymentStatus::Covered)
        <p class="text-sm text-lime-700">{{ __('Covered by your order :order.', ['order' => $licenceRequest->coveringOrder?->ulid]) }}</p>
        @break
@endswitch
