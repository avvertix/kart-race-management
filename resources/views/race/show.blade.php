<x-app-layout>
    <x-slot name="title">
        {{ $race->title }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>


        <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

            <div class="grid grid-flow-col-dense gap-4">
                <div class="text-lg p-4 bg-white shadow rounded">
                    <p>{{ __('Race registration open until') }}</p>
                    <p class="text-3xl font-black">
                        <x-time :value="$race->registration_opens_at" :timezone="$race->timezone" /> ▸
                        <x-time :value="$race->registration_closes_at" :timezone="$race->timezone" />
                    </p>
                    <p>{{ __('Race status') }} <x-race-status :value="$race->status" /></p>
                </div>
                <div class="text-lg p-4 bg-white shadow rounded">
                    <p>{{ __('participants') }}</p>
                    <p class="text-3xl font-black">{{ $statistics->total }}</p>
                    <p>{{ $statistics->confirmed }} {{ __('confirmed') }}, {{ $statistics->transponders }} {{ __('transponders') }}</p>
                </div>
            </div>

            <div class="mt-6">
                @include('race-registration.partials.participant-limit-banner')
            </div>

            <div class="mt-6 flex gap-6">
                <div class="prose prose-zinc prose-p:mb-0 prose-table:mt-2 w-1/3">
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
                <div class="prose prose-zinc prose-p:mb-0 prose-table:mt-2 w-1/3">
                    <p class="font-bold">{{ __('Participants per engine manufacturer') }}</p>

                    <table>
                        @foreach ($participantsPerEngine as $item)
                            <tr>
                                <td>{{ $item->engine_manufacturer }}</td>
                                <td>{{ $item->total }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>

        </div>

</x-app-layout>
