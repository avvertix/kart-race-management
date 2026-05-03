<x-app-layout>
    <x-slot name="title">
        {{ __('Drivers and Competitors') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
                {{ __('Drivers and Competitors') }}
            </h2>
            <x-button  >
                {{ __('Claim a participation') }}
            </x-button>
        </div>
    </x-slot>

    <div class="pb-12">
        <div class="px-4 sm:px-6 lg:px-8 space-y-8">

            <div class="space-y-3">
                <div class="p-4 bg-white rounded border border-zinc-200 space-y-1">
                    <p class="text-sm text-zinc-600">
                        {{ __('Link race registrations to your account. Search by licence number and then press link next to the registration you want to connect. Connected registrations can be used to speed-up the race registration process.') }}
                    </p>
                    <livewire:link-past-races />
                </div>
            </div>

            <p class="text-sm">
                {{ __('Driver registrations linked to your account. These are registrations done for past races you can reuse.') }}
            </p>

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Driver') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Category') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Competitor') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Last race') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900"></th>
                </x-slot>

                @forelse ($linkedParticipants as $participant)
                    <tr>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            <span class="font-mono px-2 py-1 rounded bg-orange-100 text-orange-700 print:bg-orange-100">{{ $participant->bib }}</span>
                            {{ $participant->first_name }} {{ $participant->last_name }}
                            Show licence
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            category
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            competitor
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            {{ $participant->race?->title ?? '-' }}
                            @if ($participant->race?->championship)
                                &middot; {{ $participant->race->championship->title }}
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            Button to register to next race
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="p-10 flex flex-col gap-4">

                                <p class="text-zinc-600 p-4">{{ __('No linked registrations yet. Search for past races using your licence number, link them to your account.') }}</p>
    
                                <livewire:link-past-races />
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            

        </div>
    </div>
</x-app-layout>
