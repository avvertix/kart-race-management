<div class="flex justify-between mb-4">
    <div>
        <button type="button" class="text-3xl font-bold flex items-center gap-2"
            wire:click.prevent="select(null)"
            >
            <span class="font-mono px-2 py-1 rounded bg-orange-100 text-orange-700 print:bg-orange-100">{{ $item->bib }}</span>
            <span>{{ $item->first_name }} {{ $item->last_name }}</span>
        </button>
        <div class="flex items-center mt-2 gap-4 mb-1">
            @if ($item->wildcard)
                <span class="inline-flex items-center rounded-md bg-pink-50 px-2 py-1 text-xs font-medium text-pink-700 ring-1 ring-inset ring-pink-700/10">{{ __('wildcard') }}</span>
            @endif
            <p class="text-xl">{{ $item->racingCategory?->name ?? __('no category') }} / {{ $item->engine }}</p>
        </div>
        @if ($item->racingCategory?->tire)
            <p class="text-xl">{{ $item->racingCategory?->tire->name }}</p>
        @endif
    </div>
    <div class="flex gap-3 items-start">
        <span wire:loading wire:target="confirm({{ $item->getKey() }})">
            {{ __('Saving...') }}
        </span>

        <div class="flex flex-col flex-wrap gap-4">
            <div class="flex gap-2 justify-end">
                @can('update', $item)
                    @if ($item->registration_completed_at)
                        <x-secondary-button class="inline-flex gap-1" wire:click.prevent="confirm({{ $item->getKey() }})">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Completed') }}
                        </x-secondary-button>
                    @elseif ($item->confirmed_at)
                        <x-button wire:click.prevent="markAsComplete({{ $item->getKey() }})">{{ __('Complete registration') }}</x-button>
            
                        {{-- <x-secondary-button class="inline-flex gap-1" wire:click.prevent="confirm({{ $item->getKey() }})">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Confirmed') }}
                        </x-button> --}}
                    @else
                        <x-button wire:click.prevent="confirm({{ $item->getKey() }})">{{ __('Confirm') }}</x-button>
                    @endif
                @endcan
                
                @can('update', $item)
                    <div class="flex gap-1">
                        <x-secondary-button-link href="{{ route('participants.edit', $item) }}">{{ __('Edit') }}<span class="sr-only">, {{ $item->first_name }} {{ $item->last_name }}</span></x-button-link>
                    </div>
                @endcan
                <div class="flex gap-1">
                    <x-secondary-button wire:click.prevent="select(null)">
                        {{ __('Collapse') }}
                    </x-button>
                </div>
            
            </div>
            

            <div class="flex gap-2 items-center justify-end">
                @unless ($item->registration_completed_at)
                
                    @can('create', \App\Model\Tire::class)
                        @if ($item->tires_count == 0)
                            <x-secondary-button-link href="{{ route('participants.tires.create', $item) }}">{{ __('Assign Tires') }}</x-secondary-button-link>
                        @endif
                    @endcan
                @endunless
                @can('viewAny', \App\Model\Tire::class)
                    <x-secondary-button-link href="{{ route('participants.tires.index', $item) }}">{{ __('Tires') }}</x-secondary-button-link>
                @endcan
            
                @can('viewAny', \App\Model\Transponder::class)
                    <x-secondary-button-link href="{{ route('participants.transponders.index', $item) }}">{{ __('Transponder') }}</x-secondary-button-link>
                @endcan
            </div>
        </div>
    </div>
</div>

@can('update', $item)
    @if ($race->isNationalOrInternational())
        <div class="{{ $item->wasProcessedForOutOfZone() ? 'bg-zinc-100' : 'bg-yellow-200' }} mb-4 p-2 flex gap-2 flex-col md:flex-row justify-between md:items-center">

            <div>
                <p><strong>{{ __('This race is part of a zonal championship.') }}</strong></p>
                @if ($item->wasProcessedForOutOfZone())
                    <p>{{ __('Out of zone status: :status', ['status' => $item->outOfZoneStatus()]) }}</p>
                @else            
                    <p>{{ __('Please check if the participant is out of zone and mark it accordingly.') }}</p>
                @endif
            </div>

            <div class="flex gap-4">
                <x-secondary-button class="inline-flex gap-1" wire:click.prevent="markAsOutOfZone({{ $item->getKey() }}, false)">
                    {{ __('Within zone') }}
                </x-secondary-button>

                <x-secondary-button class="inline-flex gap-1" wire:click.prevent="markAsOutOfZone({{ $item->getKey() }})">
                    {{ __('Out of zone') }}
                </x-secondary-button>

            </div>
        </div>
    @endif
@endcan

@if ($item->reservations->isNotEmpty())
    
<div class="border border-red-500 p-2 mb-6 shadow-md shadow-red-300">
    <p class="font-bold text-red-900">{{ __('Check the race number!') }}</p>
    <p>{{ __('The number appears to be reserved, but it has not been possible to confirm that the reservation is for this driver. Please check the driver\'s licence and name given for the reservation.') }}</p>

    <ul class="mt-2">
    @foreach ($item->reservations as $reservation)
        <li class="space-x-2">
            <span class="font-mono px-1 py-0.5 bg-orange-100 text-orange-700 print:bg-orange-100">{{ $reservation->bib }}</span>
            {{ $reservation->driver }}
            <span class="font-mono font-bold">{{ $reservation->driver_licence ?? __('Licence not specified') }}</span>
        </li>
    @endforeach
    </ul>
</div>
    
@endif


<div class="flex gap-6">

    @if(auth()->user()->hasPermission('payment:view'))
        <div class="pb-2 mb-2 border-b border-zinc-300">
            <p class="font-bold md:col-span-2">{{ __('Race participation price') }}</p>
            <div class="flex gap-2 items-baseline">
                <x-price class="font-mono">{{ $item->price()->last() }}</x-price>

                @if ($item->use_bonus)
                    <span class="text-sm bg-indigo-100 text-indigo-700 px-2 py-1 rounded">{{ __('Bonus')}}</span>
                @endif

                <livewire:change-participant-payment-channel :participant="$item" :key="$item->getKey()" />

                @foreach ($item->payments as $payment)
                    <a class="text-orange-600 hover:text-orange-900" href="{{ $payment->downloadUrl }}" target="_blank">{{ __('Receipt uploaded on') }} <x-time :value="$payment->created_at" /></a>
                @endforeach

            </div>
        </div>
    @endif

    @if(auth()->user()->isAdmin())
        <div class="pb-2 mb-2 border-b border-zinc-300">
            <p class="font-bold md:col-span-2">{{ __('Alias') }}</p>
            <div class="flex gap-2 items-baseline">
                <livewire:change-participant-alias :participant="$item" :key="'alias-' . $item->getKey()" />
            </div>
        </div>
    @endif

    @if(auth()->user()->isAdmin())
        <div class="pb-2 mb-2 border-b border-zinc-300">
            <p class="font-bold md:col-span-2">{{ __('Notes') }}</p>
            <div class="flex gap-2 items-baseline">
                <livewire:change-participant-notes :participant="$item" :key="'notes-' . $item->getKey()" />
            </div>
        </div>
    @endif
    
    

</div>

<div class="grid md:grid-cols-2 mb-2">
    <p class="font-bold md:col-span-2">{{ __('Driver') }}</p>
    <p>
        {{ $item->first_name }}
        {{ $item->last_name }}
    </p>
    <p>{{ $item->driver['fiscal_code'] ?? '' }}</p>
    <p>
        {{ $item->driver['nationality'] }}
        {{ $item->licence_type?->localizedName() }}
        <span class="font-mono font-bold">{{ $item->driver['licence_number'] }}</span>
    </p>
    <p>
        {{ $item->driver['email'] }}
        {{ $item->driver['phone'] }}
    </p>
    @can('update', $item)
        <p>
            {{ __('Birth :place on :date', [
                'place' => $item->driver['birth_place'],
                'date' => $item->driver['birth_date'],
            ]) }}
        </p>
        <p>
            {{ __('Medical certificate expires on :date', [
                'date' => $item->driver['medical_certificate_expiration_date'],
            ]) }}
        </p>
        <p>
            {{ __('Residence in :address, :city :province :postal_code', [
                'address' => $item->driver['residence_address']['address'] ?? null,
                'city' => $item->driver['residence_address']['city'] ?? null,
                'postal_code' => $item->driver['residence_address']['postal_code'] ?? null,
                'province' => $item->driver['residence_address']['province'] ?? null,
            ]) }}
        </p>
    @endcan
</div>
@can('update', $item)
    <div class="grid md:grid-cols-2">
        <div class="mb-2">
            <p class="font-bold">{{ __('Competitor') }}</p>
            @if ($item->competitor)
                <p>
                    {{ $item->competitor['first_name'] }}
                    {{ $item->competitor['last_name'] }}
                </p>
                <p>{{ $item->competitor['fiscal_code'] ?? '' }}</p>
                <p>
                    {{ $item->competitor['nationality'] }}
                    {{ \App\Models\CompetitorLicence::from($item->competitor['licence_type'])->localizedName() }}
                    <span class="font-mono font-bold">{{ $item->competitor['licence_number'] }}</span>
                </p>
                <p>
                    {{ $item->competitor['email'] }}
                    {{ $item->competitor['phone'] }}
                </p>
                <p>
                    {{ __('Birth :place on :date', [
                        'place' => $item->competitor['birth_place'],
                        'date' => $item->competitor['birth_date'],
                    ]) }}
                </p>
                <p>
                    {{ __('Residence in :address, :city :province :postal_code', [
                        'address' => $item->competitor['residence_address']['address'] ?? null,
                        'city' => $item->competitor['residence_address']['city'] ?? null,
                        'postal_code' => $item->competitor['residence_address']['postal_code'] ?? null,
                        'province' => $item->competitor['residence_address']['province'] ?? null,
                    ]) }}
                </p>
            @else
                <p>{{ __('No competitor specified') }}</p>
            @endif
        </div>
        <div class="mb-2">
            <p class="font-bold">{{ __('Mechanic') }}</p>
            @if ($item->mechanic)
                <p>
                    {{ $item->mechanic['name'] }}
                    {{ $item->mechanic['licence_number'] }}
                </p>
            @else
                <p>{{ __('No mechanic specified') }}</p>
            @endif
        </div>
    </div>
@endcan
<div class="mt-6 grid md:grid-cols-2">

    @foreach ($item->vehicles as $vehicle)
        <div class="grid sm:grid-cols-2 gap-1">
            <div class="">
                <span class="text-sm text-zinc-500 block">{{ __('Chassis') }}</span>
                {{ $vehicle['chassis_manufacturer'] }}
            </div>
            <div class="">
                <span class="text-sm text-zinc-500 block">{{ __('Engine') }}</span>
                {{ $vehicle['engine_manufacturer'] }}
                {{ $vehicle['engine_model'] }}
            </div>
            <div class="col-span-2">
                <span class="text-sm text-zinc-500 block">{{ __('Oil') }}</span>
                {{ $vehicle['oil_manufacturer'] }}
                {{ $vehicle['oil_type'] }}
                {{ $vehicle['oil_percentage'] }}
            </div>
        </div>
    @endforeach
</div>

<div class="mt-6 flex justify-between gap-2 items-baseline">
    @can('update', $item)
        <div class="flex gap-2 items-baseline">
            <p class="font-bold">
                {{ __('Participant id') }} <span class="font-mono">{{ $item->getKey() }}</span>
            </p>

            <form wire:submit="resendSignatureNotification({{ $item->getKey() }})">
                <x-button type="submit" class="">
                    {{ __('Resend Verification Email') }}
                </x-button>
            </form>
        </div>
    @endcan

    @can('delete', $item)
        <div class="flex gap-1 relative" x-data="{confirm: false}">

            <x-danger-button type="button" @click="confirm = !confirm" class="" >
                {{ __('Remove participant') }}
            </x-danger-button>

            <div class="absolute px-4 py-2 bottom-0 right-0 min-w-96 border border-red-300 ring-0 ring-red-300 shadow-md rounded bg-white" @click.outside="confirm = !confirm" x-show="confirm" x-transition x-cloak x-trap="confirm">
            
                <form class="" action="{{ route('participants.destroy', $item) }}" method="POST">
                    @method('DELETE')
                    @csrf

                    <p class="mb-2 font-bold">{{ __('Remove participant') }}</p>

                    <div class="mb-3 text-sm">
                        <p>{{ __("You're about to remove :driver from the participants. This action cannot be undone.", ['driver' => "{$item->first_name} {$item->last_name}"]) }}</p>
                        <p>{{ __('Are you sure you want to remove this participant?') }}</p>
                    </div>

                    <div class="flex gap-2 items-center">
                        <x-danger-button type="submit" class="">
                            {{ __('Remove participant') }}
                        </x-danger-button>

                        <x-secondary-button type="button" class="" @click="confirm = !confirm">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                    </div>
                </form>
            
            </div>
        </div>
    @endcan
</div>
