<x-app-layout>
    <x-slot name="title">
        {{ __('My licences') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
                {{ __('My licences') }}
            </h2>
            <div class="flex flex-wrap gap-2">
                @can('create', \App\Models\LicenceRequest::class)
                    <form method="POST" action="{{ route('licence-requests.store') }}">
                        @csrf
                        <x-button>{{ __('Renew licence') }}</x-button>
                    </form>
                @endcan
                <form method="POST" action="{{ route('promo-codes.find') }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <div>
                        <x-label for="promo_code_find" value="{{ __('Have a promo code?') }}" />
                        <x-input id="promo_code_find" type="text" name="code" class="mt-1 block w-44 font-mono uppercase" required />
                        <x-input-error for="code" class="mt-1" />
                    </div>
                    <x-secondary-button type="submit">{{ __('Open') }}</x-secondary-button>
                </form>
                @can('create', \App\Models\Licence::class)
                    <x-secondary-button-link href="{{ route('my-licences.create') }}">
                        {{ __('Add a licence I already have') }}
                    </x-secondary-button-link>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="pb-12">
        <div class="px-4 sm:px-6 lg:px-8 space-y-4">

            @foreach ($coveredRenewals as $coveredRenewal)
                <div class="rounded-md border border-lime-300 bg-lime-50 p-4 space-y-2">
                    <p class="font-medium text-lime-900">{{ __('Renewal covered by :package', ['package' => $coveredRenewal->lines->pluck('description')->join(', ')]) }}</p>
                    <p class="text-sm text-lime-900">{{ __('Start the licence request: it asks for no payment.') }}</p>
                    <form method="POST" action="{{ route('licence-requests.store') }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        <input type="hidden" name="order" value="{{ $coveredRenewal->ulid }}">
                        @if ($licences->whereIn('id', $renewableLicenceIds)->isNotEmpty())
                            <div>
                                <x-label for="renew_{{ $coveredRenewal->ulid }}" value="{{ __('Renew') }}" />
                                <select id="renew_{{ $coveredRenewal->ulid }}" name="licence" class="mt-1 rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50">
                                    @foreach ($licences->whereIn('id', $renewableLicenceIds) as $renewable)
                                        <option value="{{ $renewable->ulid }}">{{ $renewable->holder->fullName() }} &middot; {{ $renewable->number }}</option>
                                    @endforeach
                                    <option value="">{{ __('a licence that is not in your account') }}</option>
                                </select>
                            </div>
                        @endif
                        <x-button type="submit">{{ __('Start your licence renewal') }}</x-button>
                    </form>
                    <x-input-error for="order" class="mt-2" />
                </div>
            @endforeach

            <p class="text-sm text-zinc-600 max-w-prose">{{ __('The licences of the people you act for: yourself, your children or the drivers of your team. Each licence holds its holder\'s details.') }}</p>

            <p class="text-sm text-zinc-600 max-w-prose">
                {{ __('Licence renewals are requested here. For a first licence, write to the organizer:') }}
                <a href="mailto:{{ config('races.organizer.email') }}" class="text-orange-600 hover:text-orange-900">{{ config('races.organizer.email') }}</a>
            </p>

            @if ($licenceRequests->isNotEmpty())
                <section class="space-y-2">
                    <h3 class="font-semibold text-zinc-800">{{ __('Open requests') }}</h3>
                    <ul class="divide-y divide-zinc-200 rounded-md border border-zinc-200 bg-white">
                        @foreach ($licenceRequests as $licenceRequest)
                            <li class="flex flex-wrap items-center gap-2 p-3">
                                <div class="flex-1 min-w-48">
                                    <p class="font-medium text-zinc-900">{{ $licenceRequest->holder?->fullName() ?? __('Holder not filled in yet') }}</p>
                                    <p class="text-sm text-zinc-500">{{ $licenceRequest->licenceTypeLabel() ?? __('Licence not chosen yet') }} &middot; {{ __('For :year', ['year' => $licenceRequest->validity_year]) }} &middot; {{ $licenceRequest->status->requesterLabel() }}</p>
                                </div>
                                <a href="{{ route('licence-requests.show', $licenceRequest) }}" class="text-sm font-medium text-orange-600 hover:text-orange-900">{{ __('Open') }}</a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Holder') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Licence') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Year') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Status') }}</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">{{ __('Open') }}</span>
                    </th>
                </x-slot>

                @forelse ($licences as $item)
                    <tr>
                        <td class="py-4 pl-4 pr-3 text-sm text-zinc-900 sm:pl-6 font-medium">{{ $item->holder->fullName() }}</td>
                        <td class="px-3 py-4 text-sm text-zinc-500">
                            <p class="text-zinc-900 font-mono">{{ $item->number }}</p>
                            <p>{{ $item->provider->label() }} &middot; {{ $item->licenceTypeLabel() }}</p>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->validity_year }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">@include('my-licence.partials.status', ['licence' => $item])</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <div class="flex justify-end items-center gap-3">
                                @if (in_array($item->getKey(), $renewableLicenceIds, true))
                                    <form method="POST" action="{{ route('licence-requests.store') }}">
                                        @csrf
                                        <input type="hidden" name="licence" value="{{ $item->ulid }}">
                                        <button type="submit" class="text-orange-600 hover:text-orange-900">{{ __('Renew for :year', ['year' => $renewalYear]) }}<span class="sr-only">, {{ $item->number }}</span></button>
                                    </form>
                                @endif
                                <a href="{{ route('my-licences.show', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Open') }}<span class="sr-only">, {{ $item->number }}</span></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-4 space-y-2 text-center">
                            <p>{{ __('No licences yet. Renew one, or add a licence you already have.') }}</p>
                        </td>
                    </tr>
                @endforelse
            </x-table>

        </div>
    </div>
</x-app-layout>
