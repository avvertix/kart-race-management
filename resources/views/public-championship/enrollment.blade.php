<x-app-layout>
    <x-slot name="title">
        {{ __('Enroll in :championship', ['championship' => $championship->title]) }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">{{ $championship->title }}</h2>
        <p class="text-sm text-zinc-500">{{ __('Championship enrollment') }}</p>
    </x-slot>

    @php
        $timezone = config('races.timezone');
        $isOpen = $settings->isOpen();
        $formatDate = fn (\Carbon\CarbonInterface $value) => $value->setTimezone($timezone)->isoFormat('D MMMM YYYY HH:mm');
    @endphp

    <div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-4">
                @if ($championship->description)
                    <p class="text-zinc-700 whitespace-pre-line">{{ $championship->description }}</p>
                @endif

                <section @class([
                    'rounded-md border p-4 space-y-1',
                    'border-lime-300 bg-lime-50' => $isOpen,
                    'border-zinc-200 bg-white' => ! $isOpen,
                ]) role="status">
                    @if ($isOpen)
                        <p class="font-semibold text-lime-900">{{ __('Enrollment is open') }}</p>
                        <p class="text-sm text-lime-900">{{ __('Until :date.', ['date' => $formatDate($settings->closes_at)]) }}</p>
                    @elseif ($settings->opensLater())
                        <p class="font-semibold text-zinc-900">{{ __('Enrollment opens on :date', ['date' => $formatDate($settings->opens_at)]) }}</p>
                        @if ($settings->closes_at)
                            <p class="text-sm text-zinc-600">{{ __('Until :date.', ['date' => $formatDate($settings->closes_at)]) }}</p>
                        @endif
                    @else
                        <p class="font-semibold text-zinc-900">{{ __('Enrollment is closed') }}</p>
                    @endif
                </section>

                <p class="text-sm text-zinc-600">{{ __('Enrollment is optional: you can always register for single races.') }}</p>
            </div>

            <aside>
                <div class="rounded-md border border-zinc-200 bg-white p-4 space-y-2">
                    <h3 class="text-sm text-zinc-500">{{ __('Price') }}</h3>
                    @if ($settings->isFree())
                        <p class="text-2xl">{{ __('Free') }}</p>
                    @elseif ($price === null)
                        <p class="text-zinc-700">{{ __('Not available at the moment') }}</p>
                    @else
                        <p class="text-2xl font-mono tabular-nums">{{ $price->label() }}</p>
                        @if ($price->isRange())
                            <p class="text-sm text-zinc-600">{{ __('The organizer confirms the final amount after reviewing your enrollment.') }}</p>
                        @endif
                    @endif
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
