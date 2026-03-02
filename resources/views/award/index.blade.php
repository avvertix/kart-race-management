<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ __('Awards') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="actions">
        @can('create', \App\Models\ChampionshipAward::class)
            <x-button-link href="{{ route('championships.awards.create', ['championship' => $championship, 'type' => 'category']) }}">
                {{ __('Add category award') }}
            </x-button-link>
            <x-button-link href="{{ route('championships.awards.create', ['championship' => $championship, 'type' => 'overall']) }}">
                {{ __('Add overall award') }}
            </x-button-link>
        @endcan
    </x-slot>

    <x-table>
        <x-slot name="head">
            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Name') }}</th>
            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Type') }}</th>
            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Ranking mode') }}</th>
            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                <span class="sr-only">{{ __('Actions') }}</span>
            </th>
        </x-slot>

        @forelse ($awards as $item)
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-zinc-900 sm:pl-6">
                    <a href="{{ route('awards.show', $item) }}" class="text-orange-600 hover:text-orange-900">{{ $item->name }}</a>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                    {{ $item->type->localizedName() }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                    @if($item->isCategoryAward())
                        {{ $item->ranking_mode->localizedName() }}
                        @if($item->ranking_mode === \App\Models\AwardRankingMode::BestN)
                            ({{ $item->best_n }})
                        @endif
                    @else
                        —
                    @endif
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                    @can('update', $item)
                        <a href="{{ route('awards.edit', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Edit') }}</a>
                    @endcan
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-3 py-4 space-y-2 text-center">
                    <p>{{ __('No awards configured.') }}</p>
                    @can('create', \App\Models\ChampionshipAward::class)
                        <p>
                            <x-button-link href="{{ route('championships.awards.create', ['championship' => $championship, 'type' => 'category']) }}">
                                {{ __('Add category award') }}
                            </x-button-link>
                        </p>
                    @endcan
                </td>
            </tr>
        @endforelse
    </x-table>

</x-championship-page-layout>
