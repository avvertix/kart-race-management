{{-- The payment records of an Enrollment, oldest first, with what is paid so far. Expects $enrollment with payments loaded. --}}
@php($records = $enrollment->payments->sortBy('id'))
@if ($records->isNotEmpty())
    <div class="not-prose space-y-2">
        <p class="text-sm text-zinc-700">{{ __('Paid :paid of :amount', ['paid' => $enrollment->currency->format($enrollment->paidAmount()), 'amount' => $enrollment->currency->format($enrollment->amount)]) }}</p>
        <ul class="divide-y divide-zinc-200 rounded-md border border-zinc-200 bg-white text-sm">
            @foreach ($records as $record)
                <li class="flex flex-wrap items-center gap-x-2 px-3 py-2">
                    <x-time :value="$record->created_at" />
                    <span>&middot; {{ $record->channel?->localizedName() }}</span>
                    @if ($record->amount !== null)
                        <span class="font-mono tabular-nums">&middot; {{ $enrollment->currency->format($record->amount) }}</span>
                    @endif
                    <span>&middot; {{ $record->status?->label() }}</span>
                    @if ($record->hasProof())
                        <a href="{{ $record->downloadUrl }}" target="_blank" class="text-orange-600 hover:text-orange-900">{{ __('Open the proof') }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
