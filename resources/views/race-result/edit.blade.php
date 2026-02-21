<x-app-layout>
    <x-slot name="title">
        {{ __('Edit result') }} - {{ $runResult->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8" x-data="{
        activeRow: null,
        setParticipant(rowIndex, id, name) {
            const hiddenInput = document.getElementById('participant_id_' + rowIndex);
            const display = document.getElementById('participant_display_' + rowIndex);
            if (hiddenInput) hiddenInput.value = id;
            if (display) {
                display.textContent = name;
                display.classList.remove('bg-amber-100', 'text-amber-800');
                display.classList.add('bg-green-100', 'text-green-800');
            }
            this.activeRow = null;
        },
        unlinkParticipant(rowIndex) {
            const hiddenInput = document.getElementById('participant_id_' + rowIndex);
            const display = document.getElementById('participant_display_' + rowIndex);
            if (hiddenInput) hiddenInput.value = '';
            if (display) {
                display.textContent = '{{ __('Unlinked') }}';
                display.classList.remove('bg-green-100', 'text-green-800');
                display.classList.add('bg-amber-100', 'text-amber-800');
            }
        }
    }" x-on:participant-selected.window="if (activeRow !== null) { setParticipant(activeRow, $event.detail[0].id, $event.detail[0].first_name + ' ' + $event.detail[0].last_name) }">

        <div class="p-4 bg-white rounded max-w-6xl">

            <form action="{{ route('results.update', $runResult) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-6 space-y-4 max-w-xl">
                    <h3 class="text-lg font-bold">{{ __('Run result details') }}</h3>

                    <div>
                        <x-label for="title" value="{{ __('Title') }}" />
                        <x-input id="title" type="text" name="title" class="mt-1 block w-full" :value="old('title', $runResult->title)" required />
                        <x-input-error for="title" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="run_type" value="{{ __('Session') }}" />
                        <select id="run_type" name="run_type" class="mt-1 block w-full border-zinc-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm" required>
                            @foreach ($runTypes as $type)
                                <option value="{{ $type->value }}" @selected(old('run_type', $runResult->run_type->value) == $type->value)>{{ $type->localizedName() }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="run_type" class="mt-2" />
                    </div>
                </div>

                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-2">{{ __('Participant results') }}</h3>

                    <div class="mb-3" x-show="activeRow !== null" x-cloak>
                        <p class="text-sm text-zinc-600 mb-1">{{ __('Search participant within race') }}</p>
                        @livewire('race-participant-search', ['race' => $race])
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr>
                                    <td class="px-2 text-xs">{{ __('Pos.') }}</td>
                                    <td class="px-2 text-xs">{{ __('Bib') }}</td>
                                    <td class="px-2 text-xs">{{ __('Name') }}</td>
                                    <td class="px-2 text-xs">{{ __('Category') }}</td>
                                    <td class="px-2 text-xs">{{ __('Linked participant') }}</td>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($participantResults as $index => $participantResult)
                                    <tr wire:key="pr-{{ $participantResult->getKey() }}">
                                        <td class="px-2 py-2 border-b">
                                            @if ($participantResult->is_dnf || $participantResult->is_dns || $participantResult->is_dq)
                                                {{ $participantResult->status->localizedName() }}
                                            @else
                                                {{ $participantResult->position }}
                                            @endif
                                        </td>
                                        <td class="px-2 py-2 border-b">
                                            <input type="hidden" name="participant_results[{{ $index }}][id]" value="{{ $participantResult->getKey() }}">
                                            <input type="number" name="participant_results[{{ $index }}][bib]" value="{{ old("participant_results.{$index}.bib", $participantResult->bib) }}" class="w-20 border-zinc-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm text-sm" required>
                                            <x-input-error for="participant_results.{{ $index }}.bib" class="mt-1" />
                                        </td>
                                        <td class="px-2 py-2 border-b">
                                            <input type="text" name="participant_results[{{ $index }}][name]" value="{{ old("participant_results.{$index}.name", $participantResult->name) }}" class="w-full border-zinc-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm text-sm" required>
                                            <x-input-error for="participant_results.{{ $index }}.name" class="mt-1" />
                                        </td>
                                        <td class="px-2 py-2 border-b">
                                            <input type="text" name="participant_results[{{ $index }}][category]" value="{{ old("participant_results.{$index}.category", $participantResult->category) }}" class="w-full border-zinc-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm text-sm" required>
                                            <x-input-error for="participant_results.{{ $index }}.category" class="mt-1" />
                                        </td>
                                        <td class="px-2 py-2 border-b">
                                            <input type="hidden" name="participant_results[{{ $index }}][participant_id]" id="participant_id_{{ $index }}" value="{{ old("participant_results.{$index}.participant_id", $participantResult->participant_id) }}">
                                            <div class="flex items-center gap-2">
                                                <span id="participant_display_{{ $index }}" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $participantResult->participant_id ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                                                    {{ $participantResult->participant_id ? $participantResult->participant?->first_name . ' ' . $participantResult->participant?->last_name : __('Unlinked') }}
                                                </span>
                                                <button type="button" x-on:click="activeRow = {{ $index }}" class="text-xs underline cursor-pointer text-blue-600">{{ __('Link') }}</button>
                                                @if ($participantResult->participant_id)
                                                    <button type="button" x-on:click="unlinkParticipant({{ $index }})" class="text-xs underline cursor-pointer text-red-600">{{ __('Unlink') }}</button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <x-button type="submit">
                        {{ __('Save') }}
                    </x-button>
                    <a href="{{ route('results.show', $runResult) }}" class="underline text-sm">{{ __('Cancel') }}</a>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
