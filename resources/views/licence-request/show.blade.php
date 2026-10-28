@php
    $order = $licenceRequest->order;
    $openChangeRequests = $licenceRequest->changeRequests->whereNull('resolved_at');
    $flaggedDocuments = $licenceRequest->documents->where('status', \App\Enums\LicenceRequestDocumentStatus::Flagged);

    $timeline = array_filter([
        [__('Started'), $licenceRequest->created_at],
        [__('Submitted'), $licenceRequest->submitted_at],
        [__('Forwarded to the provider'), $licenceRequest->forwarded_at],
        [__('Licence emitted'), $licenceRequest->emitted_at],
        [__('Rejected'), $licenceRequest->rejected_at],
    ], fn (array $event) => $event[1] !== null);
@endphp

<x-app-layout>
    <x-slot name="title">
        {{ __('Licence request') }}
    </x-slot>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex flex-wrap gap-2">
                <span><a href="{{ route('my-licences.index') }}">{{ __('My licences') }}</a></span>
                <span>/</span>
                <span>{{ $licenceRequest->holder ? __('Licence request for :holder', ['holder' => $licenceRequest->holder->fullName()]) : __('Licence request') }}</span>
            </h2>
            @can('update', $licenceRequest)
                <x-button-link href="{{ route('licence-requests.edit', $licenceRequest) }}">
                    {{ $licenceRequest->status === \App\Enums\LicenceRequestStatus::ChangesRequested ? __('Fix the flagged parts') : __('Continue the request') }}
                </x-button-link>
            @endcan
        </div>
        <p class="text-sm text-zinc-500">{{ $licenceRequest->product->title }} &middot; {{ $licenceRequest->status->label() }}</p>
    </x-slot>

    <div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8 max-w-3xl space-y-6">

        <x-validation-errors />

        @if ($licenceRequest->status === \App\Enums\LicenceRequestStatus::Rejected && $licenceRequest->rejection_reason)
            <p class="rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-800">{{ $licenceRequest->rejection_reason }}</p>
        @endif

        @if ($openChangeRequests->isNotEmpty() || $flaggedDocuments->isNotEmpty())
            <section class="rounded-md border border-red-300 bg-red-50 p-4 space-y-2">
                <h3 class="font-semibold text-red-800">{{ __('Changes requested by the organizer') }}</h3>
                <ul class="list-disc pl-5 text-sm text-red-800 space-y-1">
                    @foreach ($openChangeRequests as $changeRequest)
                        <li><span class="font-semibold">{{ $changeRequest->section->label() }}:</span> {{ $changeRequest->note }}</li>
                    @endforeach
                    @foreach ($flaggedDocuments as $document)
                        <li><span class="font-semibold">{{ $document->name }}:</span> {{ $document->note }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($licenceRequest->licence)
            <p>
                <a href="{{ route('my-licences.show', $licenceRequest->licence) }}" class="text-orange-600 hover:text-orange-900">{{ __('Open the licence') }}</a>
            </p>
        @endif

        <section class="rounded-md border border-zinc-200 bg-white p-4">
            <h3 class="font-semibold text-zinc-800 mb-3">{{ __('Status') }}</h3>
            <ol class="space-y-2 border-l-2 border-zinc-200 pl-4">
                @foreach ($timeline as [$label, $date])
                    <li class="text-sm">
                        <span class="font-medium text-zinc-900">{{ $label }}</span>
                        <span class="text-zinc-500">&middot; <x-time :value="$date" /></span>
                    </li>
                @endforeach
                <li class="text-sm">
                    <span class="font-medium text-zinc-900">{{ __('Now') }}:</span> {{ $licenceRequest->status->label() }}
                </li>
            </ol>
        </section>

        @if ($order)
            <section class="rounded-md border border-zinc-200 bg-white p-4 space-y-2">
                <h3 class="font-semibold text-zinc-800">{{ __('Payment') }}</h3>
                <p class="text-sm">
                    <span class="font-mono tabular-nums">{{ $order->amountLabel() }}</span>
                    &middot; {{ $order->status->label() }}
                </p>
                @if ($order->status === \App\Enums\OrderStatus::AwaitingPayment)
                    <a href="{{ route('my-orders.show', $order) }}" class="text-sm text-orange-600 hover:text-orange-900">{{ __('Pay and upload the payment proof') }}</a>
                @elseif ($order->status === \App\Enums\OrderStatus::AwaitingPrice)
                    <p class="text-sm text-zinc-600">{{ __('The organizer confirms the final price after checking your request. You can pay once it is set.') }}</p>
                @endif
            </section>
        @endif

        @can('delete', $licenceRequest)
            <section class="rounded-md border border-zinc-200 bg-white p-4 space-y-2">
                <h3 class="font-semibold text-zinc-800">{{ __('Delete draft') }}</h3>
                <p class="text-sm text-zinc-600">{{ __('Deleting the draft removes its files and cancels its order.') }}</p>

                <flux:modal.trigger name="confirm-request-deletion">
                    <x-danger-button>{{ __('Delete draft') }}</x-danger-button>
                </flux:modal.trigger>

                <flux:modal name="confirm-request-deletion" class="max-w-lg">
                    <form method="POST" action="{{ route('licence-requests.destroy', $licenceRequest) }}" class="space-y-6">
                        @method('DELETE')
                        @csrf

                        <div>
                            <flux:heading size="lg">{{ __('Delete this licence request?') }}</flux:heading>
                            <flux:subheading>{{ __('The draft, its uploaded files and its order are removed and cannot be restored.') }}</flux:subheading>
                        </div>

                        <div class="flex justify-end gap-2">
                            <flux:modal.close>
                                <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                            </flux:modal.close>

                            <flux:button variant="danger" type="submit">{{ __('Delete draft') }}</flux:button>
                        </div>
                    </form>
                </flux:modal>
            </section>
        @endcan
    </div>
</x-app-layout>
