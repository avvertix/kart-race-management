<x-app-layout>
    <x-slot name="title">
        {{ $race->title }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>


        <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
                <div class="text-lg p-4 bg-white shadow rounded lg:col-span-2">
                    <p>{{ __('Race registration open until') }}</p>
                    <p class="text-xl md:text-3xl font-black">
                        <x-time :value="$race->registration_opens_at" :timezone="$race->timezone" /> ▸
                        <x-time :value="$race->registration_closes_at" :timezone="$race->timezone" />
                    </p>
                    <p>{{ __('Race status') }} <x-race-status :value="$race->status" /></p>
                </div>
                <div class="text-lg p-4 bg-white shadow rounded">
                    @if ($race->hasTotalParticipantLimit())
                        <p class="text-indigo-800 text-base flex items-center gap-1">
                            <x-ri-group-3-fill class="size-5 " />
                            {{ __('Limited number competition.') }}
                        </p>
                    @endif
                    <p>{{ __('participants') }}</p>
                    <p class="text-xl md:text-3xl font-black">{{ $statistics->total }}</p>
                    <p>{{ $statistics->confirmed }} {{ __('confirmed') }}, {{ $statistics->transponders }} {{ __('transponders') }}</p>
                </div>
            </div>

            <div class="mt-6 grid sm:grid-cols-2 md:grid-cols-3 gap-6">
                <div class="prose prose-zinc prose-p:mb-0 prose-table:mt-2">
                    <p class="font-bold">{{ __('Participants per category') }}</p>

                    <table>
                        @foreach ($participantsPerCategory as $item)
                            <tr>
                                <td>{{ $item->racingCategory?->name ?? __('no category') }}</td>
                                <td class="text-right"><span class="font-bold">{{ $item->total_confirmed }}</span> / {{ $item->total }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <div class="prose prose-zinc prose-p:mb-0 prose-table:mt-2">
                    <p class="font-bold">{{ __('Participants per engine manufacturer') }}</p>

                    <table>
                        @foreach ($participantsPerEngine as $item)
                            <tr>
                                <td>{{ $item->engine_manufacturer }}</td>
                                <td class="text-right">{{ $item->total }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>

        </div>

</x-app-layout>
