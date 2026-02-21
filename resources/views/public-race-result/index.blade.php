<x-app-layout>
    <x-slot name="title">
        {{ __('Results') }} - {{ $race->title }}
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

        <h3 class="text-lg font-bold mb-4">{{ __('Results') }}</h3>

        @forelse ($groupedRunResults as $runTypeName => $results)
            <h4 class="font-semibold text-sm text-zinc-700 mt-6 mb-2">{{ $runTypeName }}</h4>

            <table class="w-full text-sm">
                <thead>
                    <tr>
                        <td class="w-7/12 text-xs">{{ __('Title') }}</td>
                        <td class="w-5/12 text-xs">{{ __('Participants') }}</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($results as $runResult)
                        <tr>
                            <td class="px-2 py-3 border-b">
                                <a class="underline" href="{{ route('public.results.show', $runResult) }}">{{ $runResult->title }}</a>
                            </td>
                            <td class="px-2 py-3 border-b">
                                {{ $runResult->participant_results_count }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <p class="text-zinc-600 p-4">{{ __('No published results.') }}</p>
        @endforelse
    </div>
</x-app-layout>
