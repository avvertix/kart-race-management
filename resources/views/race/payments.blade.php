<x-app-layout>
    <x-slot name="title">
        {{ $race->title }} - {{ __('Payments') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>

    <div class="px-4 sm:px-6 lg:px-8">

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            @foreach ($summary as $item)
                <div class="p-4 bg-white shadow rounded">
                    <p class="text-3xl font-black">
                        {{ $item['count'] }}
                        @if ($item['channel'] !== null)
                            <span class="text-base font-normal text-zinc-500">/ {{ $summary->sum('count') }}</span>
                        @endif
                    </p>
                    <p class="font-medium">{{ $item['channel']?->localizedName() ?? __('Not set') }}</p>
                    @if ($item['channel'] !== null)
                        <p class="text-zinc-600 text-sm"><x-price>{{ $item['total'] }}</x-price> / <x-price>{{ $item['expected'] }}</x-price></p>
                    @endif
                </div>
            @endforeach
        </div>

        <form method="GET" action="{{ route('races.payments', $race) }}" class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3 mb-4">
            <x-search-input
                id="payment_search"
                type="text"
                placeholder="{{ __('Search participant using bib, name or last name') }}"
                name="s"
                class="w-full md:grow"
                :value="$search"
            />

            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                <select name="channel" class="border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500 text-sm w-full sm:w-auto">
                    <option value="">{{ __('All payment channels') }}</option>
                    @foreach(\App\Models\PaymentChannelType::cases() as $channel)
                        <option value="{{ $channel->value }}" @selected($filterChannel === (string) $channel->value)>{{ $channel->localizedName() }}</option>
                    @endforeach
                    <option value="none" @selected($filterChannel === 'none')>{{ __('Not set') }}</option>
                </select>

                <select name="confirmed" class="border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500 text-sm w-full sm:w-auto">
                    <option value="">{{ __('All participants') }}</option>
                    <option value="confirmed" @selected($filterConfirmed === 'confirmed')>{{ __('Payment confirmed') }}</option>
                    <option value="unconfirmed" @selected($filterConfirmed === 'unconfirmed')>{{ __('Payment not confirmed') }}</option>
                </select>

                <x-button type="submit">{{ __('Apply filter') }}</x-button>

                @if ($search || $filterChannel || $filterConfirmed)
                    <x-secondary-button-link href="{{ route('races.payments', $race) }}" class="inline-flex items-center text-sm text-zinc-500 hover:text-zinc-800">{{ __('Clear all filters') }}</x-secondary-button-link>
                @endif
            </div>
        </form>

        <x-table>
            <x-slot name="head">
                <th scope="col" class="w-4/12 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-600 sm:pl-6">{{ __('Bib') }} ▼ / {{ __('Driver') }}</th>
                <th scope="col" class=" px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Amount') }}</th>
                <th scope="col" class=" px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Payment Channel') }}</th>
                <th scope="col" class=" px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Bank transfer reason') }}</th>
                <th scope="col" class=" px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Registration date') }}</th>
                <th scope="col" class=" px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Payment proof') }}</th>
                <th scope="col" class=" px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Confirmed') }}</th>
            </x-slot>

            @forelse ($participants as $item)
                @php $transferReason = $item->id . ' ' . $item->full_name . ' iscrizione gara' @endphp
                <tr class="relative {{ $item->payment_confirmed_at ? 'bg-green-50' : '' }}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-zinc-900 sm:pl-6 font-medium">
                        <a href="{{ route('races.participants.index', ['race' => $race, 'pid' => $item->id]) }}">
                        <span class="font-mono text-lg inline-block bg-gray-100 px-2 py-1 rounded mr-2">{{ $item->bib }}</span>
                        {{ $item->first_name }} {{ $item->last_name }}
                        </a>
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
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        <form action="{{ route('participants.confirm-payment', $item) }}" method="POST">
                            @csrf
                            @if ($item->payment_confirmed_at)
                                <button type="submit" class="inline-flex items-center gap-1 text-green-700 hover:text-green-900">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 shrink-0">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    <x-time :value="$item->payment_confirmed_at" />
                                </button>
                            @else
                                @if ($item->payment_channel)
                                    <button type="submit" class="text-orange-600 hover:text-orange-900 font-medium ">
                                        {{ __('Confirm payment') }}
                                    </button>
                                @endif
                            @endif
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-3 py-4 text-center text-zinc-500">
                        @if ($search || $filterChannel || $filterConfirmed)
                            {{ __('No participants match the current filters.') }}
                        @else
                            {{ __('No participants at the moment') }}
                        @endif
                    </td>
                </tr>
            @endforelse
        </x-table>

    </div>
</x-app-layout>
