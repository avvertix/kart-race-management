<x-app-layout>
    <x-slot name="header">
        <div class="relative pb-5 sm:pb-0 print:hidden print:pb-0">
            <h2 class="font-semibold text-3xl text-zinc-800 leading-tight mb-1">
                {{ __('Your participation to :race', ['race' => $race->title]) }}
            </h2>

            <div class="prose prose-zinc">
                <p class="">{{ $race->period }} / {{ $race->track }}</p>
                <p class="font-bold">{{ __('You must present yourself to the race track the day before of the race to complete the registration process.') }}</p>
            </div>
            
        </div>

    </x-slot>

    <div class="print:hidden print:p-0 mb-6 px-4 sm:px-6 lg:px-8 flex flex-col gap-6">

        <div class="space-y-2">
            <h3 class="text-xl font-bold flex gap-2 items-center">
                1. {{ __('Signature') }}
            </h3>

            <p class="prose prose-zinc">{{ __('We need to verify the email address so we can use it for communication.') }}</p>

            @unless ($participant->hasSignedTheRequest())
            
                <p class="prose prose-zinc">{{ __('We sent an email to :driver_email with a link to ensure the address exists.', ['driver_email' => $participant->email])}} {{ __('The link is valid for :hours hours.', ['hours' => 12]) }}</p>

                @if (session('status') == 'verification-link-sent')
                    <div class="mb-4 font-medium text-sm text-green-700 border border-green-400">
                        {{ __('A new verification link has been sent to the email address you provided.') }}
                    </div>
                @endif
                
            
                <form method="POST" action="{{ url()->signedRoute('registration-verification.send', $participant->signedUrlParameters()) }}">
                    @csrf

                    <input type="hidden" name="participant" value="{{ $participant->uuid }}">
    
                    <p>
                        <x-button type="submit">
                            {{ __('Resend Verification Email') }}
                        </x-button>
                    </p>
                </form>
            @else

                <p class="prose prose-zinc">{{ __('Your identity is confirmed using the email :driver_email.', ['driver_email' => $participant->email]) }}</p>
                
            @endunless

        </div>

        <div class="space-y-2">
            <h3 class="text-xl font-bold flex gap-2 items-center">
                2. {{ __('Payment') }}
            </h3>

            <div class="max-w-7xl ">
                <h4 class="text-xl mb-1">{{ __('Race participation price') }} <x-price class="font-mono">{{ $participant->price()->last() }}</x-price></h4>

                <div class="grid lg:gap-4 lg:grid-cols-2">
                    <div class="prose prose-zinc">
                        <p class="mb-0">{{ __('Race participation can be paid via:') }}</p>

                        <ul class="mt-0">
                            <li>{{ __('Credit card or cash at the race track') }}</li>
                            <li>{{ __('Bank transfer (until :date)', ['date' => $lastAcceptedDateForBankTransfer->isoFormat('D MMM YYYY')]) }}</li>
                        </ul>

                        <p>{{ __('It may take up to five business days for the bank transfer payment to be confirmed. If the payment date falls after the deadline, you will be asked to pay by credit card or in cash at the race track.') }}</p>

                        @if (! $bankTransferAvailable)
                            <p class="not-prose flex gap-2 rounded-md text-sm text-amber-700 border border-amber-400 bg-amber-50 px-2 py-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 shrink-0">
                                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                                {{ __('Payment is only accepted by credit card or cash at the race track.') }}
                            </p>
                        @else
                            <p class="bg-white p-2 shadow border border-zinc-200 rounded ">{{ $bank->bank_holder }}
                                <br>{{ $bank->bank_name }}
                                <br>
                                <span x-data="{ copied: false }" class="inline-flex items-center gap-2">
                                    <span class="font-mono">{{ $bank->bank_account }}</span>
                                    <button
                                        type="button"
                                        x-on:click="navigator.clipboard.writeText('{{ $bank->bank_account }}'.replace(/\s+/g, '')).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                        class="text-zinc-400 hover:text-zinc-700 transition-colors"
                                        :title="copied ? '{{ __('Copied!') }}' : '{{ __('Copy IBAN') }}'"
                                    >
                                        <template x-if="!copied">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                                <path d="M7 3.5A1.5 1.5 0 018.5 2h3.879a1.5 1.5 0 011.06.44l3.122 3.12A1.5 1.5 0 0117 6.622V12.5a1.5 1.5 0 01-1.5 1.5h-1v-3.379a3 3 0 00-.879-2.121L10.5 5.379A3 3 0 008.379 4.5H7v-1z" />
                                                <path d="M4.5 6A1.5 1.5 0 003 7.5v9A1.5 1.5 0 004.5 18h7a1.5 1.5 0 001.5-1.5v-5.879a1.5 1.5 0 00-.44-1.06L9.44 6.439A1.5 1.5 0 008.378 6H4.5z" />
                                            </svg>
                                        </template>
                                        <template x-if="copied">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-green-600">
                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                            </svg>
                                        </template>
                                    </button>
                                </span>
                            </p>

                            <p>{{ __('Use the following text as the bank transfer reason:') }}</p>
                            @php $transferReason = $participant->id . ' ' . $participant->full_name . ' iscrizione gara' @endphp
                            <div x-data="{ copied: false }" class="not-prose flex items-center gap-2 bg-white shadow border border-zinc-200 rounded px-3 py-2 font-mono text-sm">
                                <span class="flex-1 select-all">{{ $transferReason }}</span>
                                <button
                                    type="button"
                                    x-on:click="navigator.clipboard.writeText('{{ $transferReason }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                    class="shrink-0 text-zinc-500 hover:text-zinc-800 transition-colors"
                                    :title="copied ? '{{ __('Copied!') }}' : '{{ __('Copy') }}'"
                                >
                                    <template x-if="!copied">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                                            <path d="M7 3.5A1.5 1.5 0 018.5 2h3.879a1.5 1.5 0 011.06.44l3.122 3.12A1.5 1.5 0 0117 6.622V12.5a1.5 1.5 0 01-1.5 1.5h-1v-3.379a3 3 0 00-.879-2.121L10.5 5.379A3 3 0 008.379 4.5H7v-1z" />
                                            <path d="M4.5 6A1.5 1.5 0 003 7.5v9A1.5 1.5 0 004.5 18h7a1.5 1.5 0 001.5-1.5v-5.879a1.5 1.5 0 00-.44-1.06L9.44 6.439A1.5 1.5 0 008.378 6H4.5z" />
                                        </svg>
                                    </template>
                                    <template x-if="copied">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-green-600">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                    </template>
                                </button>
                            </div>

                            @if ($participant->payments->isEmpty())
                                <p>{{ __('Once paid upload the bank transfer confirmation slip. Ensure that the sender\'s name, the transfer amount, the given reason, the date of the transfer and the bank account number are clearly visible. Transfers that cannot be matched to a participant will not be reimbursed.') }}</p>

                                @if (session('status') == 'payment-uploaded')
                                    <div class="mb-4 font-medium text-sm text-green-700 border border-green-400">
                                        {{ __('Thanks for uploading the bank transfer confirmation slip.') }}
                                    </div>
                                @endif

                                @include('race-registration.partials.payment-upload-form')

                            @endif

                            @unless ($participant->payments->isEmpty())

                                @if (session('status') == 'payment-uploaded')
                                    <div class="mb-4 font-medium text-sm text-green-700 border border-green-400">
                                        {{ __('Thanks for uploading the bank transfer confirmation slip.') }}
                                    </div>
                                @endif

                                <div class="prose prose-zinc">
                                    <ul>
                                        @foreach ($participant->payments as $item)
                                            <li><a href="{{ $item->downloadUrl }}" target="_blank">{{ __('Confirmation slip uploaded on') }} <x-time :value="$item->created_at" /></a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endunless
                        @endif
                    </div>
                    <div class="row-start-1 lg:row-auto prose prose-zinc">
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
                </div>
                    
            </div>

        </div>
            
        <div class="space-y-2">
            <h3 class="text-xl font-bold flex gap-2 items-center">
                3. {{ __('Transponder and/or Tires') }}
            </h3>
            <p class="prose prose-zinc">{{ __('Go to the race secretary and pick transponder and/or tires.') }}</p>
        </div>

    </div>


    <div class="py-6 px-4 sm:px-6 lg:px-8 print:py-0">
        <div class=" space-y-6">

            <div class="">
                <h3 class="text-xl font-bold mb-1">{{ $race->title }}</h3>
                <p class="text-base">{{ $championship->title }}</p>
                <p class="text-zinc-700 mb-1">{{ $race->period }} / {{ $race->track }}</p>
            </div>
            
            <div class="p-4 -mx-4 shadow-lg bg-white rounded-md mb-6 print:shadow-none flex max-w-7xl print:max-w-none">

                <div class="lg:w-2/3">
                    <h3 class="text-3xl font-bold flex items-center gap-2">
                        <span class="font-mono px-2 py-1 rounded bg-orange-100 text-orange-700 print:bg-orange-100">{{ $participant->bib }}</span>
                        <span>{{ $participant->first_name }} {{ $participant->last_name }}</span>
                    </h3>
                    <p class="mb-1 text-xl">{{ $participant->racingCategory?->name }} / {{ $participant->engine }}</p>
                    @if ($participant->racingCategory?->tire)
                        <p class="mb-6 text-xl">{{ $participant->racingCategory?->tire->name }}</p>
                    @endif
                    
                    <div class="grid md:grid-cols-2 mb-2">
                        <p class="font-bold md:col-span-2">{{ __('Driver') }}</p>
                        <p>
                            {{ $participant->first_name }}
                            {{ $participant->last_name }}
                        </p>
                        <p>
                            {{ $participant->driver['nationality'] }}
                            {{ $participant->licence_type?->name }}
                            {{ $participant->driver['licence_number'] }}
                        </p>
                        <p>
                            {{ $participant->driver['email'] }}
                            {{ $participant->driver['phone'] }}
                        </p>
                        <p>
                            {{ __('Birth :place on :date', [
                                'place' => $participant->driver['birth_place'],
                                'date' => $participant->driver['birth_date'],
                            ]) }}
                        </p>
                        <p>
                            {{ __('Medical certificate expires on :date', [
                                'date' => $participant->driver['medical_certificate_expiration_date'],
                            ]) }}
                        </p>
                        <p>
                            {{ __('Residence in :address, :city :province :postal_code', [
                                'address' => $participant->driver['residence_address']['address'] ?? null,
                                'city' => $participant->driver['residence_address']['city'] ?? null,
                                'postal_code' => $participant->driver['residence_address']['postal_code'] ?? null,
                                'province' => $participant->driver['residence_address']['province'] ?? null,
                            ]) }}
                        </p>
                    </div>
                    <div class="grid md:grid-cols-2">
                        <div class="grid md:grid-cols-3 mb-2">
                            <p class="font-bold md:col-span-3">{{ __('Competitor') }}</p>
                            @if ($participant->competitor)
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="competitor_first_name" value="{{ __('Name') }}*" />
                                    {{ $participant->competitor['first_name'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="competitor_last_name" value="{{ __('Surname') }}*" />
                                    {{ $participant->competitor['last_name'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="competitor_licence_type" value="{{ __('Licence Type') }}*" />
                                    {{ $participant->competitor['licence_type'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="competitor_licence_number" value="{{ __('Licence Number') }}*" />
                                    {{ $participant->competitor['licence_number'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="competitor_nationality" value="{{ __('Nationality') }}*" />
                                    {{ $participant->competitor['nationality'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="competitor_email" value="{{ __('E-Mail') }}*" />
                                    {{ $participant->competitor['email'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="competitor_phone" value="{{ __('Phone number') }}*" />
                                    {{ $participant->competitor['phone'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="competitor_birth_date" value="{{ __('Birth date') }}*" />
                                    {{ $participant->competitor['birth_date'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="competitor_birth_place" value="{{ __('Birth place') }}*" />
                                    {{ $participant->competitor['birth_place'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="competitor_residence" value="{{ __('Residence address') }}*" />
                                    {{ $participant->competitor['residence_address']['address'] ?? null }}
                                    {{ $participant->competitor['residence_address']['city'] ?? null }}
                                    {{ $participant->competitor['residence_address']['postal_code'] ?? null }}
                                    {{ $participant->competitor['residence_address']['province'] ?? null }}
                                </div>
                            @else
                                <p>{{ __('No competitor specified') }}</p>
                            @endif
                        </div>
                        <div class="mb-2">
                            <p class="font-bold">{{ __('Mechanic') }}</p>
                            @if ($participant->mechanic)
                                <p>
                                    {{ $participant->mechanic['name'] }}
                                    {{ $participant->mechanic['licence_number'] }}
                                </p>
                            @else
                                <p>{{ __('No mechanic specified') }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="mt-6 grid md:grid-cols-2">
                    
                        @foreach ($participant->vehicles as $vehicle)
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
                </div>
                <div class="hidden lg:w-1/3 lg:flex justify-end p-4">
                    {!! $participant->qrCodeSvg() !!}
                </div>
            </div>

            <div class="hidden mt-6 print:block space-y-2">
                <p>{{ __('Participation detail') }}</p>
                <p class="font-mono text-lg">{{ $participant->id }} / {{ $participant->uuid }}</p>
                <p>{{ $participant->created_at }}</p>
            </div>
            
        </div>
        <div class="mt-8 prose prose-zinc print:hidden">
            <p class="">{{ __('This receipt is uniquely generated for you. You can access it anytime by clicking on "View the participation" in the email you received. For easier access you can add it to your favourites, share it or send it to your phone by scanning the QR code.') }}</p>
            <p class="">{{ __('Please bring this receipt with you to the race (printed version or PDF).') }}</p>
        </div>
    </div>
</x-app-layout>
