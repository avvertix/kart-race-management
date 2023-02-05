<x-app-layout>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>


    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="grid grid-flow-col-dense gap-4">
                <div class="text-lg p-4 bg-white shadow rounded">
                    <p>{{ __('Race registration open until') }}</p>
                    <p class="text-3xl font-black">{{ $race->registration_closes_at }}</p>
                    <p>{{ __('Race status') }} {{ $race->status }}</p>
                </div>
                <div class="text-lg p-4 bg-white shadow rounded">
                    <p>{{ __('participants') }}</p>
                    <p class="text-3xl font-black">{{ $statistics->total }}</p>
                    <p>{{ $statistics->confirmed }} {{ __('confirmed') }}</p>
                </div>
            </div>

            <div class="mt-6">
                <div class="prose prose-zinc prose-p:mb-0 prose-table:mt-2 w-1/3">
                    <p class="font-bold">{{ __('Participants per category') }}</p>

                    <table>
                        @foreach ($participantsPerCategory as $item)
                            <tr>
                                <td>{{ $item->category()->name }}</td>
                                <td>{{ $item->total }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <div></div>
            </div>

        </div>
    </div>
</x-app-layout>
