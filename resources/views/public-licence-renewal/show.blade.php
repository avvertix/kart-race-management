<x-app-layout>
    <x-slot name="title">
        {{ __('Renew your licence') }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">{{ __('Renew your licence') }}</h2>
        <p class="text-sm text-zinc-500">{{ __('Online, for :year', ['year' => $renewalYear]) }}</p>
    </x-slot>

    <div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6 max-w-prose">
                <p class="text-zinc-700">{{ __('Renew your sporting licence with the organizer without going to an office: fill in the request, upload the documents and pay once the organizer checked them. One account can renew the licences of several people, e.g. you and your child, or the drivers of your team.') }}</p>

                <section class="space-y-2">
                    <h3 class="font-semibold text-zinc-800">{{ __('Licences renewed online') }}</h3>
                    <ul class="space-y-1">
                        @foreach ($licenceTypes as $provider => $types)
                            <li class="text-zinc-700"><span class="font-medium text-zinc-900">{{ $provider }}</span>: {{ $types->join(', ') }}</li>
                        @endforeach
                    </ul>
                    <p class="text-sm text-zinc-600">
                        {{ __('For a first licence, write to the organizer:') }}
                        <a href="mailto:{{ config('races.organizer.email') }}" class="text-orange-600 hover:text-orange-900">{{ config('races.organizer.email') }}</a>
                    </p>
                </section>

                <section class="space-y-2">
                    <h3 class="font-semibold text-zinc-800">{{ __('What to prepare') }}</h3>
                    <ul class="divide-y divide-zinc-200 rounded-md border border-zinc-200 bg-white">
                        @foreach ($documents as $item)
                            @php($document = $item['document'])
                            <li class="p-3">
                                <p class="font-medium text-zinc-900">
                                    {{ $document->label() }}
                                    @if ($document->kind() === \App\Enums\LicenceDocumentKind::SignedForm)
                                        <span class="ml-1 text-xs font-normal px-2 py-0.5 rounded-full bg-zinc-100 text-zinc-700">{{ __('Pre-filled form to sign') }}</span>
                                    @endif
                                </p>
                                @if ($document->description())
                                    <p class="text-sm text-zinc-600">{{ $document->description() }}</p>
                                @endif
                                @if ($document->isUnderageOnly())
                                    <p class="text-sm text-zinc-600">{{ __('Only when the holder is underage.') }}</p>
                                @endif
                                @if ($document->isAlsoForEachParent())
                                    <p class="text-sm text-zinc-600">{{ __('Also for each parent or guardian of an underage holder.') }}</p>
                                @endif
                                @if ($item['onlyFor'])
                                    <p class="text-sm text-zinc-600">{{ __('Only for :providers licences.', ['providers' => $item['onlyFor']]) }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="space-y-2">
                    <h3 class="font-semibold text-zinc-800">{{ __('How it works') }}</h3>
                    <ol class="list-decimal pl-5 space-y-1 text-zinc-700">
                        <li>{{ __('Sign in and open My licences. Start from last year\'s licence when it is in your account, or from your race registrations: they fill in the request for you.') }}</li>
                        <li>{{ __('Choose the licence, fill in the holder\'s details and upload the documents. You can stop at any time: the request stays saved as a draft.') }}</li>
                        <li>{{ __('Submit the request. The organizer checks it and may ask you to fix something.') }}</li>
                        <li>{{ __('Pay the amount the organizer sets. The licence is then requested to the provider and appears in your account.') }}</li>
                    </ol>
                    <p class="text-sm text-zinc-600">{{ __('Enrolling in a championship? Some enrollment options include the licence renewal: choose one of them and you pay nothing more for it.') }}</p>
                </section>
            </div>

            <aside class="space-y-4">
                <section class="rounded-md border border-orange-300 bg-white p-4 space-y-3">
                    <p class="font-semibold text-zinc-900">{{ __('Renewal for :year', ['year' => $renewalYear]) }}</p>
                    @if ($renewalPrice)
                        <p class="text-zinc-700">{{ __('Price') }}: <span class="font-mono tabular-nums">{{ $renewalPrice }}</span></p>
                    @endif

                    @auth
                        <x-button-link href="{{ route('my-licences.index') }}">{{ __('Go to my licences') }}</x-button-link>
                    @else
                        <div class="flex flex-wrap gap-2">
                            <x-button-link href="{{ route('login') }}">{{ __('Sign in to renew') }}</x-button-link>
                            @if (Route::has('register'))
                                <x-secondary-button-link href="{{ route('register') }}">{{ __('Create an account') }}</x-secondary-button-link>
                            @endif
                        </div>
                    @endauth
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
