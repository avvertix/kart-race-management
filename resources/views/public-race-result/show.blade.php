<x-app-layout>
    <x-slot name="title">
        {{ $runResult->title }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ $championship->title }} &mdash; {{ $race->title }}
        </h2>
        <p class="text-sm text-zinc-500">
            {{ $race->period }} &middot; {{ $race->track }}
            @if ($race->point_multiplier)
                &middot; {{ __('Coefficient') }} &times;{{ $race->point_multiplier }}
            @endif
            @if ($race->rain)
                &middot; {{ __('Wet race') }}
            @endif
        </p>
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

        <div class="mb-4 flex flex-col items-start justify-between print:hidden">
            <a class="text-sm underline text-zinc-600 hover:text-zinc-700" href="{{ route('public.races.results.index', $race) }}">{{ __('← Back to results') }}</a>
            <h3 class="text-lg font-bold">{{ $runResult->title }}</h3>
            <p class="text-sm text-zinc-500">{{ $runResult->run_type->localizedName() }}</p>
        </div>

        {{-- Print-only header --}}
        <div class="hidden print:block mb-4">
            <p class="text-base font-bold">{{ $runResult->title }}</p>
            <p class="text-sm text-zinc-500">{{ $runResult->run_type->localizedName() }}</p>
        </div>

        <x-table>
            <x-slot name="head">
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Pos.') }}</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Bib') }}</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Name') }}</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Category') }}</th>
                @if ($runResult->run_type->isRace())
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Total time') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Laps') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900 hidden lg:table-cell print:table-cell">{{ __('Gap') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900 hidden lg:table-cell print:table-cell">{{ __('Interval') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900 hidden lg:table-cell print:table-cell">{{ __('Best lap') }}</th>
                @endif
                @if ($runResult->run_type->isQualify())
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Best lap') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900 hidden lg:table-cell print:table-cell">{{ __('Gap') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900 hidden lg:table-cell print:table-cell">{{ __('Interval') }}</th>
                @endif
            </x-slot>

            @forelse ($participantResults as $participantResult)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6  text-zinc-900">
                        @if ($participantResult->is_dnf || $participantResult->is_dns || $participantResult->is_dq)
                            <span class="text-zinc-400">{{ $participantResult->status->localizedName() }}</span>
                        @else
                            {{ $participantResult->position }}
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm font-bold text-zinc-900">{{ $participantResult->bib }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-zinc-900">{{ $participantResult->name }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $participantResult->category }}</td>
                    @if ($runResult->run_type->isRace())
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-900">{{ $participantResult->total_race_time }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $participantResult->laps }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500 hidden lg:table-cell print:table-cell">{{ $participantResult->gap_from_leader }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500 hidden lg:table-cell print:table-cell">{{ $participantResult->gap_from_previous }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500 hidden lg:table-cell print:table-cell">{{ $participantResult->best_lap_time }}</td>
                    @endif
                    @if ($runResult->run_type->isQualify())
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-900">{{ $participantResult->best_lap_time }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500 hidden lg:table-cell print:table-cell">{{ $participantResult->gap_from_leader }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500 hidden lg:table-cell print:table-cell">{{ $participantResult->gap_from_previous }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $runResult->run_type->isRace() ? 9 : ($runResult->run_type->isQualify() ? 7 : 4) }}" class="px-3 py-4 text-center text-sm text-zinc-500">
                        {{ __('No participant results.') }}
                    </td>
                </tr>
            @endforelse
        </x-table>

    </div>
</x-app-layout>
