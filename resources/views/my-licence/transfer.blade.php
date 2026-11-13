<x-app-layout>
    <x-slot name="title">
        {{ __('Add to my licences') }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('my-licences.index') }}">{{ __('My licences') }}</a></span>
            <span>/</span>
            <span>{{ __('Add to my licences') }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">

            <p class="text-sm text-zinc-600 max-w-prose">
                {{ __('From :driver\'s participation in :championship, confirmed by the organizer. The licence is added as verified, ready to be renewed or enrolled.', [
                    'driver' => $participation->first_name.' '.$participation->last_name,
                    'championship' => $participation->championship->title,
                ]) }}
            </p>

            <form method="POST" action="{{ route('my-licences.transfers.store', $participant) }}">
                @csrf

                @include('my-licence.partials.form', ['transfer' => $transfer])

                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div></div>
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="px-4 py-5">
                            <x-button>
                                {{ __('Add to my licences') }}
                            </x-button>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
