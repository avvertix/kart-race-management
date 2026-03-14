<x-app-layout>
    <x-slot name="title">
        {{ $race->title }} - {{ __('Payments') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>

    <div class="px-4 sm:px-6 lg:px-8">

        <x-table>
            <x-slot name="head">
                <th scope="col" class="w-4/12 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-600 sm:pl-6">{{ __('Bib') }} ▼ / {{ __('Driver') }}</th>
                <th scope="col" class="w-2/12 px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Amount') }}</th>
                <th scope="col" class="w-2/12 px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Payment Channel') }}</th>
                <th scope="col" class="w-3/12 px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Bank transfer reason') }}</th>
                <th scope="col" class="w-2/12 px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Registration date') }}</th>
                <th scope="col" class="w-3/12 px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Payment proof') }}</th>
            </x-slot>

            @forelse ($participants as $item)
                @php $transferReason = $item->id . ' ' . $item->full_name . ' iscrizione gara' @endphp
                <tr class="relative">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-zinc-900 sm:pl-6 font-medium">
                        <span class="font-mono text-lg inline-block bg-gray-100 px-2 py-1 rounded mr-2">{{ $item->bib }}</span>
                        {{ $item->first_name }} {{ $item->last_name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-zinc-900">
                        <x-price class="font-mono">{{ $item->price()->last() }}</x-price>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-zinc-900">
                        @if ($item->payment_channel)
                            {{ $item->payment_channel->localizedName() }}
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-4 text-zinc-900 text-sm font-mono">
                        {{ $transferReason }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-zinc-900 text-sm">
                        <x-time :value="$item->created_at" />
                    </td>
                    <td class="px-3 py-4 text-zinc-900 text-sm">
                        @forelse ($item->payments as $payment)
                            <a class="text-orange-600 hover:text-orange-900 block" href="{{ $payment->downloadUrl }}" target="_blank">
                                <x-time :value="$payment->created_at" />
                            </a>
                        @empty
                            <span class="text-zinc-400">—</span>
                        @endforelse
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-4 text-center text-zinc-500">
                        {{ __('No participants at the moment') }}
                    </td>
                </tr>
            @endforelse
        </x-table>

    </div>
</x-app-layout>
