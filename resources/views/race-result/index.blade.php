<x-app-layout>
    <x-slot name="title">
        {{ __('Results') }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

        <div class="flex justify-between mb-4 items-center">
        
            <div class="flex items-center gap-3">
                @can('update', $race)
                    <x-button-link href="{{ route('races.results.create', $race) }}">
                        {{ __('Upload results') }}
                    </x-button-link>
                    @if ($runResults->isNotEmpty())
                        <form action="{{ route('races.results.link-participants', $race) }}" method="post">
                            @csrf
                            <x-secondary-button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-zinc-300 rounded-md font-semibold text-xs text-zinc-700 uppercase tracking-widest shadow-sm hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Link all participants') }}
                            </x-secondary-button>
                        </form>
                        @livewire('assign-points-button', ['race' => $race], key('assign-all'))
                        <form action="{{ route('races.results.publish-all', $race) }}" method="post">
                            @csrf
                            <x-secondary-button type="submit">
                                {{ __('Publish all') }}
                            </x-secondary-button>
                        </form>
                    @endif
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

                <p class="space-x-4">
                    <a class="inline-flex items-center gap-2" target="_blank" href="{{ route('championships.awards.index', $race->championship) }}">
                        <x-ri-external-link-line class="size-4 text-zinc-500 shrink-0" />
                        {{ __('Championship awards') }}
                    </a>
                    <a class="inline-flex items-center gap-2" target="_blank" href="{{ route('public.races.results.index', $race) }}">
                        <x-ri-external-link-line class="size-4 text-zinc-500 shrink-0" />
                        {{ __('Public results') }}
                    </a>
                </p>
            </div>
        </div>


        @forelse ($runResults as $runType => $group)
            <h4 class="font-semibold text-lg text-zinc-700 mt-6 mb-2 flex items-center gap-2">
                {{ $group->first()->run_type->localizedName() }}
                <span class="inline-flex items-center rounded-full bg-white px-2 py-0.5 text-xs font-medium text-zinc-600">{{ $group->count() }}</span>
            </h4>

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="w-2/5 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Title') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Participants') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Published') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Upload date') }}</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">{{ __('Actions') }}</span>
                    </th>
                </x-slot>

                @foreach ($group as $runResult)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-zinc-900 sm:pl-6">
                            <a href="{{ route('results.show', $runResult) }}" class="text-orange-600 hover:text-orange-900">{{ $runResult->title }}</a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            {{ $runResult->participant_results_count }}
                            @if ($runResult->unlinked_participants_count > 0)
                                <span class="text-xs">({{ $runResult->unlinked_participants_count }} {{ __('unlinked') }})</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            @if ($runResult->isPublished())
                                <span class="text-green-600">{{ __('Published') }}</span>
                            @else
                                <span class="text-zinc-400">{{ __('Draft') }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            <x-time :value="$runResult->created_at" />
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-4">
                            @can('update', $race)
                                <a href="{{ route('results.edit', $runResult) }}" class="text-orange-600 hover:text-orange-900">{{ __('Edit') }}</a>

                                <form class="inline" action="{{ route('results.link-participants', $runResult) }}" method="post">
                                    @csrf
                                    <button type="submit" class="text-orange-600 hover:text-orange-900 cursor-pointer">{{ __('Link') }}</button>
                                </form>
                                <form class="inline" action="{{ route('results.toggle-publish', $runResult) }}" method="post">
                                    @csrf
                                    <button type="submit" class="text-orange-600 hover:text-orange-900 cursor-pointer">
                                        {{ $runResult->isPublished() ? __('Unpublish') : __('Publish') }}
                                    </button>
                                </form>
                                <form class="inline" action="{{ route('results.destroy', $runResult) }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-orange-600 hover:text-orange-900 cursor-pointer">{{ __('Delete') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @empty
            <p class="text-zinc-600 p-4">{{ __('No results uploaded.') }}</p>
        @endforelse
    </div>
</x-app-layout>
