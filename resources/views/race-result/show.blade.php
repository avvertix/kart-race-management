<x-app-layout>
    <x-slot name="title">
        {{ $runResult->title }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

        <div class="mb-4">
            <div class="flex items-center gap-3">
                <h3 class="text-lg font-bold">{{ $runResult->title }}</h3>
                @if ($runResult->isPublished())
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">{{ __('Published') }}</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-800">{{ __('Draft') }}</span>
                @endif
            </div>
            <p class="text-sm text-zinc-500">{{ $runResult->run_type->localizedName() }}</p>

            @can('update', $race)
                <form action="{{ route('results.toggle-publish', $runResult) }}" method="post" class="mt-2">
                    @csrf
                    <button type="submit" class="underline cursor-pointer text-sm">
                        {{ $runResult->isPublished() ? __('Unpublish') : __('Publish') }}
                    </button>
                </form>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr>
                        <td class="px-2 text-xs">{{ __('Pos.') }}</td>
                        <td class="px-2 text-xs">{{ __('Bib') }}</td>
                        <td class="px-2 text-xs">{{ __('Name') }}</td>
                        <td class="px-2 text-xs">{{ __('Category') }}</td>
                        @if ($runResult->run_type->isRace())
                            <td class="px-2 text-xs">{{ __('Total time') }}</td>
                            <td class="px-2 text-xs">{{ __('Laps') }}</td>
                            <td class="px-2 text-xs">{{ __('Gap') }}</td>
                            <td class="px-2 text-xs">{{ __('Interval') }}</td>
                            <td class="px-2 text-xs">{{ __('Best lap') }}</td>
                        @endif
                        @if ($runResult->run_type->isQualify())
                            <td class="px-2 text-xs">{{ __('Best lap') }}</td>
                            <td class="px-2 text-xs">{{ __('Gap') }}</td>
                            <td class="px-2 text-xs">{{ __('Interval') }}</td>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($participantResults as $participantResult)
                        <tr>
                            <td class="px-2 py-2 border-b">
                                @if ($participantResult->is_dnf || $participantResult->is_dns || $participantResult->is_dq)
                                    {{ $participantResult->status->localizedName() }}
                                @else
                                    {{ $participantResult->position }}
                                @endif
                            </td>
                            <td class="px-2 py-2 border-b font-bold">{{ $participantResult->bib }}</td>
                            <td class="px-2 py-2 border-b">
                                {{ $participantResult->name }}
                                @if (!$participantResult->participant_id)
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-800">{{ __('Unlinked') }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 border-b">{{ $participantResult->category }}</td>
                            @if ($runResult->run_type->isRace())
                                <td class="px-2 py-2 border-b">{{ $participantResult->total_race_time }}</td>
                                <td class="px-2 py-2 border-b">{{ $participantResult->laps }}</td>
                                <td class="px-2 py-2 border-b">{{ $participantResult->gap_from_leader }}</td>
                                <td class="px-2 py-2 border-b">{{ $participantResult->gap_from_previous }}</td>
                                <td class="px-2 py-2 border-b">{{ $participantResult->best_lap_time }}</td>
                            @endif
                            @if ($runResult->run_type->isQualify())
                                <td class="px-2 py-2 border-b">{{ $participantResult->best_lap_time }}</td>
                                <td class="px-2 py-2 border-b">{{ $participantResult->gap_from_leader }}</td>
                                <td class="px-2 py-2 border-b">{{ $participantResult->gap_from_previous }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            @if ($runResult->run_type->isRace())
                                <td colspan="10">
                            @elseif ($runResult->run_type->isQualify())
                                <td colspan="7">
                            @else
                                <td colspan="4">
                            @endif
                                <p class="text-zinc-600 p-4">{{ __('No participant results.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
