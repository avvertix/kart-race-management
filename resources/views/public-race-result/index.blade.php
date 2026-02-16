<x-app-layout>
    <x-slot name="title">
        {{ __('Results') }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ $championship->title }} &mdash; {{ $race->title }}
        </h2>
        <p class="text-sm text-zinc-500">{{ $race->period }} &middot; {{ $race->track }}</p>
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

        <h3 class="text-lg font-bold mb-4">{{ __('Results') }}</h3>

        <table class="w-full text-sm">
            <thead>
                <tr>
                    <td class="w-5/12 text-xs">{{ __('Title') }}</td>
                    <td class="w-3/12 text-xs">{{ __('Session') }}</td>
                    <td class="w-4/12 text-xs">{{ __('Participants') }}</td>
                </tr>
            </thead>
            <tbody>
                @forelse ($runResults as $runResult)
                    <tr>
                        <td class="px-2 py-3 border-b">
                            <a class="underline" href="{{ route('public.results.show', $runResult) }}">{{ $runResult->title }}</a>
                        </td>
                        <td class="px-2 py-3 border-b">
                            {{ $runResult->run_type->localizedName() }}
                        </td>
                        <td class="px-2 py-3 border-b">
                            {{ $runResult->participant_results_count }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">
                            <p class="text-zinc-600 p-4">{{ __('No published results.') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
