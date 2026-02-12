<x-app-layout>
    <x-slot name="title">
        {{ __('Driver Templates') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
                {{ __('Driver Templates') }}
            </h2>
            <x-button-link href="{{ route('drivers.create') }}" >
                {{ __('Create a driver') }}
            </x-button-link>
        </div>
    </x-slot>

    <div class="pb-12">
        <div class="px-4 sm:px-6 lg:px-8">

            <p class="mb-6 text-sm">
                {{ __('Driver templates allow you to store anagraphical information to reduce the time to complete the registration process for a race.') }}
            </p>

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Driver') }}</td>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Competitor') }}</td>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Template Name') }}</td>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Created') }}</td>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900"></td>
                </x-slot>
                
                    @forelse ($templates as $template)
                        <tr>
                            
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                                <span class="font-mono px-2 py-1 rounded bg-orange-100 text-orange-700 print:bg-orange-100">{{ $template->bib }}</span>
                                {{ $template->driver['first_name'] ?? '' }} {{ $template->driver['last_name'] ?? '' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                                @if ($template->competitor)
                                    {{ $template->competitor['first_name'] ?? '' }} {{ $template->competitor['last_name'] ?? '' }}
                                @else
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                                {{ $template->name }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                                {{ $template->created_at->diffForHumans() }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
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
            </x-table>

        </div>
    </div>
</x-app-layout>
