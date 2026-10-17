<x-app-layout>
    <x-slot name="title">
        {{ __('Copy race number reservations') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('championships.bib-reservations.index', $championship) }}">{{ $championship->title }}</a></span>
            <span>/</span>
            <span>{{ __('Copy race number reservations') }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('championships.bib-reservations.store-copy', $championship) }}">
                @csrf

                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-1">
                        <div class="px-4 sm:px-0">
                            <h3 class="text-lg font-medium leading-6 text-zinc-900">{{ __('Copy reservations from another championship') }}</h3>
                            <p class="mt-1 text-sm text-zinc-600">
                                {{ __('Copy the active reservations of a championship, or create a reservation for each driver that participated in it using the same race number.') }}
                            </p>
                            <p class="mt-1 text-sm text-zinc-600">
                                {{ __('Entries whose race number, driver or licence is already reserved or used by a participant in this championship are skipped.') }}
                            </p>
                            <p class="mt-1 text-sm text-zinc-600">
                                {{ __('Only race numbers between :min and :max are copied.', ['min' => \App\Models\BibReservation::MIN_BIB, 'max' => \App\Models\BibReservation::MAX_BIB]) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="shadow-sm sm:rounded-md sm:overflow-hidden">
                            <div class="px-4 py-5 bg-white space-y-6 sm:p-6">

                                @if($sourceChampionships->isEmpty())
                                    <div class="rounded-md bg-yellow-50 p-4">
                                        <div class="flex">
                                            <div class="ml-3">
                                                <h3 class="text-sm font-medium text-yellow-800">
                                                    {{ __('No championships available') }}
                                                </h3>
                                                <div class="mt-2 text-sm text-yellow-700">
                                                    <p>{{ __('There are no other championships with reservations or participants to copy from.') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div>
                                        <x-label for="source_championship" value="{{ __('Championship') }}" />
                                        <flux:select id="source_championship" name="source_championship" class="mt-1 block w-full" required>
                                            <option value="">{{ __('Select a championship') }}</option>
                                            @foreach($sourceChampionships as $sourceChampionship)
                                                <option value="{{ $sourceChampionship->id }}" {{ old('source_championship') == $sourceChampionship->id ? 'selected' : '' }}>
                                                    {{ $sourceChampionship->title }} ({{ $sourceChampionship->reservations_count }} {{ trans_choice('reservation|reservations', $sourceChampionship->reservations_count) }})
                                                </option>
                                            @endforeach
                                        </flux:select>
                                        <x-input-error for="source_championship" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-label for="source" value="{{ __('Copy from') }}" />
                                        <flux:select id="source" name="source" class="mt-1 block w-full" required>
                                            <option value="{{ \App\Actions\CopyChampionshipBibReservations::FROM_RESERVATIONS }}" @selected(old('source') === \App\Actions\CopyChampionshipBibReservations::FROM_RESERVATIONS)>{{ __('Reservations') }}</option>
                                            <option value="{{ \App\Actions\CopyChampionshipBibReservations::FROM_PARTICIPANTS }}" @selected(old('source') === \App\Actions\CopyChampionshipBibReservations::FROM_PARTICIPANTS)>{{ __('Participants (one per driver licence and race number)') }}</option>
                                        </flux:select>
                                        <x-input-error for="source" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-label for="reservation_expiration_date" value="{{ __('Reserved until') }}" />
                                        <x-input id="reservation_expiration_date" type="date" class="mt-1 block w-full" name="reservation_expiration_date" :value="old('reservation_expiration_date')" />
                                        <p class="mt-1 text-sm text-zinc-600">{{ __('Leave empty to keep the reservations until the end of the championship.') }}</p>
                                        <x-input-error for="reservation_expiration_date" class="mt-2" />
                                    </div>
                                @endif

                            </div>

                            @if($sourceChampionships->isNotEmpty())
                                <div class="px-4 py-3 bg-zinc-50 text-right sm:px-6">
                                    <x-button>
                                        {{ __('Copy reservations') }}
                                    </x-button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>
