<x-app-layout>
    <x-slot name="title">
        {{ $participant->full_name }} - {{ $participant->race->title }}
    </x-slot>
    <x-slot name="header">
        <div class="relative border-b-2 border-zinc-200 pb-5 sm:pb-0">
            <div class="md:flex md:items-center md:justify-between">
                <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
                    <span class="font-mono px-2 py-1 rounded bg-orange-100 text-orange-700">{{ $participant->bib }}</span>
                    {{ $participant->first_name }} {{ $participant->last_name }}
                    <p class="text-base font-light mt-1">
                        <a href="{{ route('races.participants.index', $participant->race) }}" class="hover:underline">{{ $participant->race->title }}</a>
                        &mdash; {{ $participant->championship->title }}
                    </p>
                </h2>
                <div class="mt-3 flex md:absolute md:top-3 md:right-0 md:mt-0 gap-2 flex-wrap justify-end">
                    @can('update', $participant)
                        <form method="POST" action="{{ url()->signedRoute('registration-verification.send', $participant->signedUrlParameters()) }}">
                            @csrf
                            <input type="hidden" name="participant" value="{{ $participant->uuid }}">
                            <x-secondary-button type="submit">
                                {{ __('Resend Verification Email') }}
                            </x-secondary-button>
                        </form>
                    @endcan
                    
                    @can('view', $participant)
                        <x-secondary-button-link target="_blank" href="{{ route('races.participants.print', ['race' => $race, 'pid' => $participant->getKey()]) }}">
                            {{ __('Print') }}
                        </x-secondary-button-link>
                    @endcan

                    @can('update', $participant)
                        <x-button-link href="{{ route('participants.edit', $participant) }}">
                            {{ __('Edit participant') }}
                        </x-button-link>
                    @endcan
                </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-2 text-sm">
                @if ($participant->registration_completed_at)
                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">{{ __('Registration completed') }}</span>
                @elseif ($participant->confirmed_at)
                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ __('Confirmed') }}</span>
                @else
                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-700/10">{{ __('Pending') }}</span>
                @endif

                @if ($participant->wildcard)
                    <span class="inline-flex items-center rounded-md bg-pink-50 px-2 py-1 text-xs font-medium text-pink-700 ring-1 ring-inset ring-pink-700/10">{{ __('wildcard') }}</span>
                @endif

                @if ($participant->use_bonus)
                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">{{ __('Bonus') }}</span>
                @endif

                @if ($participant->hasSignedTheRequest())
                    <span class="inline-flex items-center rounded-md bg-lime-50 px-2 py-1 text-xs font-medium text-lime-700 ring-1 ring-inset ring-lime-600/20">{{ __('Email verified') }}</span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Category, bib & payment --}}
            <div class="grid md:grid-cols-3 gap-4">

                <div class="md:col-span-2 bg-white shadow rounded-lg p-4 space-y-3">
                    <h3 class="font-semibold text-zinc-700 text-sm uppercase tracking-wide">{{ __('Race') }}</h3>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Number') }}</span>
                        <span class="font-mono text-2xl font-bold text-orange-700">{{ $participant->bib }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Category') }}</span>
                        <span class="font-medium">{{ $participant->racingCategory?->name ?? $participant->category ?? '—' }}</span>
                    </div>
                    @if ($participant->racingCategory?->tire)
                        <div>
                            <span class="block text-xs text-zinc-500">{{ __('Tire') }}</span>
                            <span>{{ $participant->racingCategory->tire->name }}</span>
                        </div>
                    @endif
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Engine') }}</span>
                        <span>{{ $participant->engine ?? '—' }}</span>
                    </div>
                </div>

                @if(auth()->user()->hasPermission('payment:view'))
                <div class="bg-white shadow rounded-lg p-4 space-y-3">
                    <h3 class="font-semibold text-zinc-700 text-sm uppercase tracking-wide">{{ __('Payment') }}</h3>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Price') }}</span>
                        <x-price class="font-mono text-lg font-bold">{{ $participant->price()->last() }}</x-price>
                    </div>
                    <div class="prose prose-zinc">
                        <table>
                            @foreach ($participant->price() as $key => $price)
                                <tr>
                                    <td class="{{ $loop->last ? 'font-bold' : ''}}">{{ $key }}</td>
                                    <td class="text-right {{ $loop->last ? 'font-bold' : ''}}"><x-price>{{ $price }}</x-price></td>
                                    <td class="min-w-[40px]">&nbsp;</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                    @if ($participant->payment_channel)
                        <div>
                            <span class="block text-xs text-zinc-500">{{ __('Channel') }}</span>
                            <livewire:change-participant-payment-channel :participant="$participant" :key="$participant->getKey()" />
                        </div>
                    @else
                        <livewire:change-participant-payment-channel :participant="$participant" :key="$participant->getKey()" />
                    @endif
                    @foreach ($participant->payments as $payment)
                        <a class="text-orange-600 hover:text-orange-900 text-sm block" href="{{ $payment->downloadUrl }}" target="_blank">
                            {{ __('Receipt uploaded on') }} <x-time :value="$payment->created_at" />
                        </a>
                    @endforeach
                    @if ($participant->payment_confirmed_at)
                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                            {{ __('Payment confirmed') }}
                        </span>
                    @endif
                </div>
                @endif
            </div>

            {{-- Tires & transponders summary --}}
            @if ($participant->tires->isNotEmpty() || $participant->transponders->isNotEmpty())
            <div class="grid md:grid-cols-2 gap-4">
                @if ($participant->tires->isNotEmpty())
                <div class="bg-white shadow rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-zinc-700 text-sm uppercase tracking-wide">{{ __('Tires') }}</h3>
                        @can('viewAny', \App\Model\Tire::class)
                            <x-secondary-button-link href="{{ route('participants.tires.index', $participant) }}" class="text-xs">{{ __('Manage') }}</x-secondary-button-link>
                        @endcan
                    </div>
                    <ul class="space-y-1">
                        @foreach ($participant->tires as $tire)
                            <li class="font-mono text-sm">{{ $tire->code }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if ($participant->transponders->isNotEmpty())
                <div class="bg-white shadow rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-zinc-700 text-sm uppercase tracking-wide">{{ __('Transponders') }}</h3>
                        @can('viewAny', \App\Model\Transponder::class)
                            <x-secondary-button-link href="{{ route('participants.transponders.index', $participant) }}" class="text-xs">{{ __('Manage') }}</x-secondary-button-link>
                        @endcan
                    </div>
                    <ul class="space-y-1">
                        @foreach ($participant->transponders as $transponder)
                            <li class="font-mono text-sm">{{ $transponder->code }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            @endif

            {{-- Driver --}}
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-zinc-200">
                    <h3 class="font-semibold text-zinc-700">{{ __('Driver') }}</h3>
                </div>
                <div class="p-4 grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Name') }}</span>
                        <span class="font-medium">{{ $participant->first_name }} {{ $participant->last_name }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Fiscal Code') }}</span>
                        <span class="font-mono">{{ $participant->driver['fiscal_code'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Licence Type') }}</span>
                        <span>{{ $participant->licence_type?->localizedName() ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Licence Number') }}</span>
                        <span class="font-mono font-bold">{{ $participant->driver['licence_number'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Nationality') }}</span>
                        <span>{{ $participant->driver['nationality'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('E-Mail') }}</span>
                        <span>{{ $participant->driver['email'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Phone number') }}</span>
                        <span>{{ $participant->driver['phone'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Birth date') }}</span>
                        <span>{{ $participant->driver['birth_date'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Birth place') }}</span>
                        <span>{{ $participant->driver['birth_place'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Date of expiration of the medical certificate') }}</span>
                        <span>{{ $participant->driver['medical_certificate_expiration_date'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Residence address') }}</span>
                        <span>
                            {{ $participant->driver['residence_address']['address'] ?? '' }}
                            {{ $participant->driver['residence_address']['city'] ?? '' }}
                            {{ $participant->driver['residence_address']['postal_code'] ?? '' }}
                            {{ $participant->driver['residence_address']['province'] ?? '' }}
                        </span>
                    </div>
                    @if (isset($participant->driver['sex']))
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Sex') }}</span>
                        <span>{{ \App\Models\Sex::from($participant->driver['sex'] ?? 30)?->localizedName() }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Competitor --}}
            @if ($participant->competitor)
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-zinc-200">
                    <h3 class="font-semibold text-zinc-700">{{ __('Competitor') }}</h3>
                </div>
                <div class="p-4 grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Name') }}</span>
                        <span class="font-medium">{{ $participant->competitor['first_name'] }} {{ $participant->competitor['last_name'] }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Fiscal Code') }}</span>
                        <span class="font-mono">{{ $participant->competitor['fiscal_code'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Licence Type') }}</span>
                        <span>{{ $participant->competitor['licence_type'] ? \App\Models\CompetitorLicence::from($participant->competitor['licence_type'])->localizedName() : '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Licence Number') }}</span>
                        <span class="font-mono font-bold">{{ $participant->competitor['licence_number'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Nationality') }}</span>
                        <span>{{ $participant->competitor['nationality'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('E-Mail') }}</span>
                        <span>{{ $participant->competitor['email'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Phone number') }}</span>
                        <span>{{ $participant->competitor['phone'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Birth date') }}</span>
                        <span>{{ $participant->competitor['birth_date'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Birth place') }}</span>
                        <span>{{ $participant->competitor['birth_place'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Residence address') }}</span>
                        <span>
                            {{ $participant->competitor['residence_address']['address'] ?? '' }}
                            {{ $participant->competitor['residence_address']['city'] ?? '' }}
                            {{ $participant->competitor['residence_address']['postal_code'] ?? '' }}
                            {{ $participant->competitor['residence_address']['province'] ?? '' }}
                        </span>
                    </div>
                </div>
            </div>
            @endif

            {{-- Mechanic --}}
            @if ($participant->mechanic)
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-zinc-200">
                    <h3 class="font-semibold text-zinc-700">{{ __('Mechanic') }}</h3>
                </div>
                <div class="p-4 grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Name and Surname') }}</span>
                        <span class="font-medium">{{ $participant->mechanic['name'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-zinc-500">{{ __('Licence number') }}</span>
                        <span class="font-mono">{{ $participant->mechanic['licence_number'] ?? '—' }}</span>
                    </div>
                </div>
            </div>
            @endif

            {{-- Vehicles --}}
            @if ($participant->vehicles->isNotEmpty())
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-zinc-200">
                    <h3 class="font-semibold text-zinc-700">{{ __('Vehicle') }}</h3>
                </div>
                <div class="p-4 space-y-6">
                    @foreach ($participant->vehicles as $vehicle)
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3 text-sm {{ !$loop->last ? 'pb-6 border-b border-zinc-200' : '' }}">
                        <div>
                            <span class="block text-xs text-zinc-500">{{ __('Chassis Manufacturer') }}</span>
                            <span class="font-medium">{{ $vehicle['chassis_manufacturer'] ?? '—' }}</span>
                        </div>
                        @if (!empty($vehicle['chassis_model']))
                        <div>
                            <span class="block text-xs text-zinc-500">{{ __('Chassis Model') }}</span>
                            <span>{{ $vehicle['chassis_model'] }}</span>
                        </div>
                        @endif
                        @if (!empty($vehicle['chassis_number']))
                        <div>
                            <span class="block text-xs text-zinc-500">{{ __('Chassis Number') }}</span>
                            <span class="font-mono">{{ $vehicle['chassis_number'] }}</span>
                        </div>
                        @endif
                        @if (!empty($vehicle['chassis_homologation']))
                        <div>
                            <span class="block text-xs text-zinc-500">{{ __('Chassis Homologation') }}</span>
                            <span class="font-mono">{{ $vehicle['chassis_homologation'] }}</span>
                        </div>
                        @endif
                        <div>
                            <span class="block text-xs text-zinc-500">{{ __('Engine Manufacturer') }}</span>
                            <span class="font-medium">{{ $vehicle['engine_manufacturer'] ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-zinc-500">{{ __('Engine Model') }}</span>
                            <span>{{ $vehicle['engine_model'] ?? '—' }}</span>
                        </div>
                        @if (!empty($vehicle['engine_number']))
                        <div>
                            <span class="block text-xs text-zinc-500">{{ __('Engine Number') }}</span>
                            <span class="font-mono">{{ $vehicle['engine_number'] }}</span>
                        </div>
                        @endif
                        @if (!empty($vehicle['engine_homologation']))
                        <div>
                            <span class="block text-xs text-zinc-500">{{ __('Engine Homologation') }}</span>
                            <span class="font-mono">{{ $vehicle['engine_homologation'] }}</span>
                        </div>
                        @endif
                        <div>
                            <span class="block text-xs text-zinc-500">{{ __('Oil Manufacturer') }}</span>
                            <span>{{ $vehicle['oil_manufacturer'] ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-zinc-500">{{ __('Oil Type') }}</span>
                            <span>{{ $vehicle['oil_type'] ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-zinc-500">{{ __('Oil Percentage') }}</span>
                            <span>{{ $vehicle['oil_percentage'] ?? '—' }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Activity log --}}
            @can('update', $participant)
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-zinc-200 flex items-center justify-between">
                    <h3 class="font-semibold text-zinc-700">{{ __('Activity') }}</h3>
                    <span class="text-xs text-zinc-500">{{ __('id') }}&nbsp;<span class="font-mono">{{ $participant->getKey() }}</span></span>
                </div>

                @forelse ($activities as $activity)
                    @php
                        $isCreated = $activity['event'] === 'created';
                    @endphp
                    <div class="px-4 py-4 {{ !$loop->last ? 'border-b border-zinc-100' : '' }}">

                        {{-- Header row --}}
                        <div class="flex flex-wrap items-center gap-2 mb-3 text-sm">
                            @if ($isCreated)
                                <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">{{ __('registered') }}</span>
                            @elseif ($activity['event'] === 'updated')
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ __('updated') }}</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600">{{ $activity['event'] }}</span>
                            @endif

                            @if ($activity['causer'])
                                <span class="text-zinc-600">{{ $activity['causer'] }}</span>
                                <span class="text-zinc-300">&bull;</span>
                            @endif
                            <x-time class="text-zinc-400" :value="$activity['date']" />
                        </div>

                        {{-- Changes --}}
                        @if ($activity['changes']->isNotEmpty())
                            @if ($isCreated)
                                {{-- Created: single "value" column, skip fields that are — --}}
                                <dl class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-1 text-sm">
                                    @foreach ($activity['changes'] as $change)
                                        @if ($change['new'] !== '—')
                                            <div>
                                                <dt class="text-xs text-zinc-400">{{ $change['field'] }}</dt>
                                                <dd class="text-zinc-800 font-medium">{{ $change['new'] }}</dd>
                                            </div>
                                        @endif
                                    @endforeach
                                </dl>
                            @else
                                {{-- Updated: before → after table --}}
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left border-b border-zinc-100">
                                            <th class="pb-1 font-medium text-xs text-zinc-400 w-1/4">{{ __('Field') }}</th>
                                            <th class="pb-1 font-medium text-xs text-zinc-400 w-5/12">{{ __('Before') }}</th>
                                            <th class="pb-1 w-4 text-zinc-300">&rarr;</th>
                                            <th class="pb-1 font-medium text-xs text-zinc-400 w-5/12">{{ __('After') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-50">
                                        @foreach ($activity['changes'] as $change)
                                            <tr>
                                                <td class="py-1.5 text-zinc-500 text-xs">{{ $change['field'] }}</td>
                                                <td class="py-1.5 text-zinc-400 line-through decoration-zinc-300">{{ $change['old'] }}</td>
                                                <td class="py-1.5 text-zinc-300">&rarr;</td>
                                                <td class="py-1.5 font-medium text-zinc-800">{{ $change['new'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-zinc-400 text-sm">{{ __('No changes recorded.') }}</div>
                @endforelse
            </div>
            @endcan

        </div>
    </div>
</x-app-layout>
