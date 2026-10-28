@if ($licence->source === \App\Enums\LicenceSource::Emitted)
    <span class="inline-block px-2 py-1 text-sm rounded-full bg-lime-100 text-lime-800">{{ $licence->source->label() }}</span>
@elseif ($licence->isVerified())
    <span class="inline-block px-2 py-1 text-sm rounded-full bg-lime-100 text-lime-800">{{ __('Declared, verified') }}</span>
@else
    <span class="inline-block px-2 py-1 text-sm rounded-full bg-amber-100 text-amber-800">{{ __('Declared, to be verified') }}</span>
@endif
