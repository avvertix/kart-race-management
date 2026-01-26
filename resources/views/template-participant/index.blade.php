<x-app-layout>
    <x-slot name="title">
        {{ __('Driver Templates') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
                {{ __('Driver Templates') }}
            </h2>
            <a href="{{ route('drivers.create') }}" class="inline-flex items-center px-4 py-2 bg-zinc-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-zinc-700 focus:bg-zinc-700 active:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Create a driver') }}
            </a>
        </div>
    </x-slot>

    <div class="pb-12">
        <div class="px-4 sm:px-6 lg:px-8">

            <div class="p-4 bg-white rounded mb-6">
                <p class="text-sm text-zinc-600">
                    {{ __('Driver templates allow you to store anagraphical information to reduce the time to complete the registration process for a race.') }}
                </p>
            </div>

            <table class="w-full text-sm bg-white rounded">
                <thead>
                    <tr>
                        <td class="px-4 py-3 border-b text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Name') }}</td>
                        <td class="px-4 py-3 border-b text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Driver') }}</td>
                        <td class="px-4 py-3 border-b text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Competitor') }}</td>
                        <td class="px-4 py-3 border-b text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Created') }}</td>
                        <td class="px-4 py-3 border-b text-xs font-medium text-zinc-500 uppercase tracking-wider"></td>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr>
                            <td class="px-4 py-3 border-b">
                                {{ $template->name }}
                            </td>
                            <td class="px-4 py-3 border-b">
                                {{ $template->driver['first_name'] ?? '' }} {{ $template->driver['last_name'] ?? '' }}
                            </td>
                            <td class="px-4 py-3 border-b">
                                @if ($template->competitor)
                                    {{ $template->competitor['first_name'] ?? '' }} {{ $template->competitor['last_name'] ?? '' }}
                                @else
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 border-b">
                                {{ $template->created_at->diffForHumans() }}
                            </td>
                            <td class="px-4 py-3 border-b">
                                <a class="underline" href="{{ route('drivers.edit', $template) }}">{{ __('Edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <p class="text-zinc-600 p-4">{{ __('No templates yet. Create one to speed up race registrations.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
    </div>
</x-app-layout>
