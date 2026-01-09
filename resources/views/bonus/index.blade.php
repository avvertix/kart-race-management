<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ __('Bonus') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="actions">
        @can('create', \App\Model\Bonus::class)
            <x-button-link href="{{ route('championships.bonuses.create', $championship) }}">
                {{ __('Add bonus') }}
            </x-button-link>
        @endcan

        @can('update', $championship)
            <x-button-link href="{{ route('championships.edit', $championship) }}">
                {{ __('Edit championship') }}
            </x-button-link>
        @endcan
    </x-slot>

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Driver') }} ▼</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Bonus type') }}</th>
                    @if($bonus_mode === \App\Models\BonusMode::CREDIT)
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Credits') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Total value') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Remaining') }}</th>
                    @else
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Balance') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Used') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Remaining') }}</th>
                    @endif
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">{{ __('Edit') }}</span>
                    </th>
                </x-slot>

            @forelse ($bonuses as $item)

                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm  text-zinc-900 sm:pl-6">
                        <p><a href="{{ route('bonuses.show', $item) }}" class="font-medium hover:text-orange-900">{{ $item->driver }}</a></p>
                        <p>{{ $item->driver_licence }}</p>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                        {{ $item->bonus_type->localizedName() }}
                    </td>
                    @if($bonus_mode === \App\Models\BonusMode::CREDIT)
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500 tabular-nums font-mono">
                            {{ $item->amount }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            <x-price>{{ $item->amount * $fixed_bonus_amount }}</x-price>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500 tabular-nums font-mono">
                            {{ $item->remaining() }}
                        </td>
                    @else
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            <x-price>{{ $item->amount }}</x-price>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500 tabular-nums font-mono">
                            {{ $item->usages()->count() }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            <x-price>{{ $item->amount }}</x-price>
                        </td>
                    @endif
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        @can('update', $item)
                            <a href="{{ route('bonuses.edit', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Edit') }}<span class="sr-only">, {{ $item->driver }}</span></a>
                        @endcan
                    </td>
                </tr>

                
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-4 space-y-2 text-center">
                        <p>{{ __('No bonus or discounts') }}</p>
                        @can('create', \App\Model\Bonus::class)
                            <p>
                                <x-button-link href="{{ route('championships.bonuses.create', $championship) }}">
                                    {{ __('Add a bonus') }}
                                </x-button-link>
                            </p>
                        @endcan
                    </td>
                </tr>
            @endforelse
            
            </x-table>
</x-championship-page-layout>