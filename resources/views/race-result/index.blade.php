<x-app-layout>
    <x-slot name="title">
        {{ __('Results') }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

        @can('update', $race)
            <div class="mb-6">
                <x-button-link href="{{ route('races.results.create', $race) }}">
                    {{ __('Upload results') }}
                </x-button-link>
            </div>
        @endcan

        <table class="w-full text-sm">
            <thead>
                <tr>
                    <td class="w-4/12 text-xs">{{ __('Title') }}</td>
                    <td class="w-2/12 text-xs">{{ __('Session') }}</td>
                    <td class="w-2/12 text-xs">{{ __('Participants') }}</td>
                    <td class="w-2/12 text-xs">{{ __('Upload date') }}</td>
                    <td class="w-2/12 text-xs"></td>
                </tr>
            </thead>
            <tbody>
                @forelse ($runResults as $runResult)
                    <tr>
                        <td class="px-2 py-3 border-b">
                            <a class="underline" href="{{ route('results.show', $runResult) }}">{{ $runResult->title }}</a>
                        </td>
                        <td class="px-2 py-3 border-b">
                            {{ $runResult->run_type->name }}
                        </td>
                        <td class="px-2 py-3 border-b">
                            {{ $runResult->participant_results_count }}
                        </td>
                        <td class="px-2 py-3 border-b">
                            <x-time :value="$runResult->created_at" />
                        </td>
                        <td class="px-2 py-3 border-b">
                            @can('update', $race)
                                <form action="{{ route('results.destroy', $runResult) }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="underline cursor-pointer">{{ __('Delete') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <p class="text-zinc-600 p-4">{{ __('No results uploaded.') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
