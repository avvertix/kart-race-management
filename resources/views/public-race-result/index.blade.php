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
            @if ($race->red_flag)
                &middot; {{ __('Red flag') }}
            @endif
        </p>
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

        @forelse ($groupedRunResults as $runTypeName => $results)
            <h4 class="font-semibold text-lg text-zinc-700 mt-6 mb-2">{{ $runTypeName }}</h4>

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Title') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Participants') }}</th>
                </x-slot>

                @foreach ($results as $runResult)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-zinc-900 sm:pl-6">
                            <a class="text-orange-600 hover:text-orange-900 underline" href="{{ route('public.results.show', $runResult) }}">{{ $runResult->title }}</a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            {{ $runResult->participant_results_count }}
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @empty
            <p class="text-zinc-600 p-4">{{ __('No published results.') }}</p>
        @endforelse

    </div>
</x-app-layout>
