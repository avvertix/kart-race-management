<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ $category->name }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="actions">
        @can('update', $category)
            <x-button-link href="{{ route('categories.edit', $category) }}">
                {{ __('Edit category') }}
            </x-button-link>
        @endcan
    </x-slot>


    <div class="pb-12">
        <div class="px-4 sm:px-6 lg:px-8 grid md:grid-cols-2 gap-4">

            <div class="p-4 bg-white shadow-xl rounded space-y-2">
                <p>
                    @if ($category->enabled)
                        <span class="inline-block px-2 py-1 text-sm rounded-full bg-lime-100 text-lime-800">{{ __('active') }}</span>
                    @elseif ($category->participants_count > 0)
                        <span class="inline-block px-2 py-1 text-sm rounded-full bg-amber-100 text-amber-800">{{ __('paused') }}</span>
                    @else
                        <span class="inline-block px-2 py-1 text-sm rounded-full bg-zinc-100 text-zinc-800">{{ __('inactive') }}</span>
                    @endif
                </p>
                <p class="font-medium">{{ $category->name }}</p>
                @if ($category->short_name)
                    <p class="text-zinc-500 text-sm">{{ $category->short_name }}</p>
                @endif
                <p>
                    @if ($category->tire)
                        <a href="{{ route('tire-options.show', $category->tire) }}" target="_blank">{{ $category->tire->name }}</a>
                    @else
                        <span class="text-zinc-400">{{ __('All tires') }}</span>
                    @endif
                </p>
            </div>

            <div class="space-y-4">
                @forelse ($activities as $activity)
                    <div class="p-4 space-y-3">
                        <div class="flex items-center text-sm text-zinc-800 gap-4">
                            <span>{{ $activity['event'] }}</span>
                            @if ($activity['causer'])
                                <span>{{ $activity['causer'] }}</span>
                            @endif
                            <x-time :value="$activity['date']" />
                        </div>

                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-zinc-800 border-b">
                                    <th class="pb-1 font-medium w-1/3">{{ __('Field') }}</th>
                                    <th class="pb-1 font-medium w-1/3">{{ __('Before') }}</th>
                                    <th class="pb-1 font-medium w-1/3">{{ __('After') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @foreach ($activity['changes'] as $change)
                                    <tr>
                                        <td class="py-1 text-zinc-800">{{ $change['field'] }}</td>
                                        <td class="py-1 text-zinc-800">{{ $change['old'] }}</td>
                                        <td class="py-1 font-medium text-zinc-900">{{ $change['new'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @empty
                    <p class="text-zinc-400 text-sm">{{ __('No changes recorded.') }}</p>
                @endforelse
            </div>

        </div>
    </div>
</x-championship-page-layout>
