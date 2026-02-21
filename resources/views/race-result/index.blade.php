<x-app-layout>
    <x-slot name="title">
        {{ __('Results') }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

        <div class="flex justify-between mb-4 items-center">
        
            <div class="">
                @can('update', $race)
                    <x-button-link href="{{ route('races.results.create', $race) }}">
                        {{ __('Upload results') }}
                    </x-button-link>
                @endcan
            </div>

            <div class="flex items-center gap-4">
                 @if ($race->point_multiplier)
                    <p class="flex items-center gap-2">
                        <x-ri-trophy-line class="size-4 text-zinc-500 shrink-0" />
                        {{ __('Coefficient') }} &times;{{ $race->point_multiplier }}
                    </p>
                @endif

                @if ($race->rain)
                    <p class="flex items-center gap-2">
                        <x-ri-heavy-showers-line class="size-4 text-zinc-500 shrink-0" />
                        {{ __('Wet race') }}
                    </p>
                @endif
            </div>
        </div>


        <table class="w-full text-sm">
            <thead>
                <tr>
                    <td class="w-4/12 text-xs">{{ __('Title') }}</td>
                    <td class="w-2/12 text-xs">{{ __('Session') }}</td>
                    <td class="w-2/12 text-xs">{{ __('Participants') }}</td>
                    <td class="w-2/12 text-xs">{{ __('Published') }}</td>
                    <td class="w-1/12 text-xs">{{ __('Upload date') }}</td>
                    <td class="w-1/12 text-xs"></td>
                </tr>
            </thead>
            <tbody>
                @forelse ($runResults as $runResult)
                    <tr>
                        <td class="px-2 py-3 border-b">
                            <a class="underline" href="{{ route('results.show', $runResult) }}">{{ $runResult->title }}</a>
                        </td>
                        <td class="px-2 py-3 border-b">
                            {{ $runResult->run_type->localizedName() }}
                        </td>
                        <td class="px-2 py-3 border-b">
                            {{ $runResult->participant_results_count }}
                        </td>
                        <td class="px-2 py-3 border-b">
                            @if ($runResult->isPublished())
                                <span class="text-green-600">{{ __('Published') }}</span>
                            @else
                                <span class="text-zinc-400">{{ __('Draft') }}</span>
                            @endif
                        </td>
                        <td class="px-2 py-3 border-b">
                            <x-time :value="$runResult->created_at" />
                        </td>
                        <td class="px-2 py-3 border-b">
                            @can('update', $race)
                                <div class="flex gap-2">
                                    <a href="{{ route('results.edit', $runResult) }}" class="underline">{{ __('Edit') }}</a>
                                    <form action="{{ route('results.toggle-publish', $runResult) }}" method="post">
                                        @csrf
                                        <button type="submit" class="underline cursor-pointer">
                                            {{ $runResult->isPublished() ? __('Unpublish') : __('Publish') }}
                                        </button>
                                    </form>
                                    <form action="{{ route('results.destroy', $runResult) }}" method="post">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="underline cursor-pointer">{{ __('Delete') }}</button>
                                    </form>
                                </div>
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
