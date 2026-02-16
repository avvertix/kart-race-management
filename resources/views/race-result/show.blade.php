<x-app-layout>
    <x-slot name="title">
        {{ $runResult->title }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

        <div class="mb-4">
            <h3 class="text-lg font-bold">{{ $runResult->title }}</h3>
            <p class="text-sm text-zinc-500">{{ $runResult->run_type->name }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr>
                        <td class="text-xs">{{ __('Pos.') }}</td>
                        <td class="text-xs">{{ __('PIC') }}</td>
                        <td class="text-xs">{{ __('Bib') }}</td>
                        <td class="text-xs">{{ __('Name') }}</td>
                        <td class="text-xs">{{ __('Category') }}</td>
                        <td class="text-xs">{{ __('Laps') }}</td>
                        <td class="text-xs">{{ __('Best lap') }}</td>
                        <td class="text-xs">{{ __('Gap') }}</td>
                        <td class="text-xs">{{ __('Status') }}</td>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($participantResults as $participantResult)
                        <tr>
                            <td class="px-2 py-2 border-b">{{ $participantResult->position }}</td>
                            <td class="px-2 py-2 border-b">{{ $participantResult->position_in_category }}</td>
                            <td class="px-2 py-2 border-b font-bold">{{ $participantResult->bib }}</td>
                            <td class="px-2 py-2 border-b">{{ $participantResult->name }}</td>
                            <td class="px-2 py-2 border-b">{{ $participantResult->category }}</td>
                            <td class="px-2 py-2 border-b">{{ $participantResult->laps }}</td>
                            <td class="px-2 py-2 border-b">{{ $participantResult->best_lap_time }}</td>
                            <td class="px-2 py-2 border-b">{{ $participantResult->gap_from_leader }}</td>
                            <td class="px-2 py-2 border-b">{{ $participantResult->status->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <p class="text-zinc-600 p-4">{{ __('No participant results.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
