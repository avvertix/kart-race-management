@if ($show)
<div {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <p data-section="true" class="font-mono text-xs/6 font-medium tracking-widest text-gray-600 uppercase">{{ __('Get started') }}</p>

    <div class="-mx-2 sm:mx-0 p-4 bg-white rounded space-y-4">
        <div>
            <h3 class="font-semibold text-zinc-900">{{ __('Link your past race registrations') }}</h3>
            <p class="text-sm text-zinc-600 mt-1">
                {{ __('Speed up future registrations by linking your previous races. Search by licence number and link your participation.') }}
            </p>
        </div>

        <x-button-link href="{{ route('drivers.index') }}">
            {{ __('Go to Drivers and competitors') }}
        </x-button-link>
    </div>
</div>
@endif
