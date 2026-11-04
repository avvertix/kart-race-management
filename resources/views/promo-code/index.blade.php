<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ __('Promo codes') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="actions">
        @can('create', \App\Models\PromoCode::class)
            <x-button-link href="{{ route('championships.promo-codes.create', $championship) }}">
                {{ __('Issue promo codes') }}
            </x-button-link>
        @endcan
    </x-slot>

    <div class="space-y-4">
        <x-table>
            <x-slot name="head">
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Code') }}</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Gives') }}</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Status') }}</th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                    <span class="sr-only">{{ __('Actions') }}</span>
                </th>
            </x-slot>

            @forelse ($promoCodes as $item)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium sm:pl-6">
                        <a href="{{ route('public.promo-codes.show', $item) }}" class="font-mono text-zinc-900 hover:text-orange-700">{{ $item->code }}</a>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->benefit() }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                        {{ $item->status->label() }}
                        @if ($item->claimed_at)
                            &middot; <x-time :value="$item->claimed_at" />
                        @endif
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        @can('revoke', $item)
                            <form method="POST" action="{{ route('promo-codes.revoke', $item) }}">
                                @csrf
                                <button type="submit" class="text-orange-600 hover:text-orange-900">{{ __('Revoke') }}<span class="sr-only">, {{ $item->code }}</span></button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-3 py-4 text-center">{{ __('No promo codes issued yet.') }}</td>
                </tr>
            @endforelse
        </x-table>

        {{ $promoCodes->links() }}
    </div>
</x-championship-page-layout>
