<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ __('Saved Penalties') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="actions">
        @can('create', \App\Models\ChampionshipPenalty::class)
            <x-button-link href="{{ route('championships.penalties.import.create', $championship) }}">
                {{ __('Import penalty templates') }}
            </x-button-link>
            <x-button-link href="{{ route('championships.penalties.create', $championship) }}">
                {{ __('Add penalty template') }}
            </x-button-link>
        @endcan
    </x-slot>

    <x-table>
        <x-slot name="head">
            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Title') }} ▼</th>
            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Description') }}</th>
            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                <span class="sr-only">{{ __('Actions') }}</span>
            </th>
        </x-slot>

        @forelse ($penalties as $penalty)
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-zinc-900 sm:pl-6">
                    {{ $penalty->title }}
                </td>
                <td class="px-3 py-4 text-sm text-zinc-500 max-w-lg truncate">
                    {{ $penalty->description }}
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-4">
                    @can('update', $penalty)
                        <a href="{{ route('penalties.edit', $penalty) }}" class="text-orange-600 hover:text-orange-900">{{ __('Edit') }}</a>
                    @endcan
                    @can('delete', $penalty)
                        <form method="POST" action="{{ route('penalties.destroy', $penalty) }}" class="inline" onsubmit="return confirm('{{ __('Delete this penalty template?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                        </form>
                    @endcan
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="px-3 py-4 space-y-2 text-center">
                    <p>{{ __('No penalty templates yet.') }}</p>
                    @can('create', \App\Models\ChampionshipPenalty::class)
                        <p>
                            <x-button-link href="{{ route('championships.penalties.create', $championship) }}">
                                {{ __('Add penalty template') }}
                            </x-button-link>
                        </p>
                    @endcan
                </td>
            </tr>
        @endforelse
    </x-table>

</x-championship-page-layout>
