<x-app-layout>
    <x-slot name="title">
        {{ $product->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('public.products.index') }}">{{ __('Enrollments and offers') }}</a></span>
            <span>/</span>
            <span>{{ $product->title }}</span>
        </h2>
        <p class="text-sm text-zinc-500">{{ $product->kind->label() }}</p>
    </x-slot>

    <div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">

            <div class="lg:col-span-2 space-y-6">
                @if ($product->description)
                    <p class="text-zinc-700 whitespace-pre-line">{{ $product->description }}</p>
                @endif

                @if ($product->championship)
                    <section>
                        <h3 class="font-semibold text-lg text-zinc-800">{{ __('What is included') }}</h3>
                        <ul class="mt-2 list-disc pl-5 text-zinc-700">
                            @if ($product->championship)
                                <li>{{ __('Enrollment in :championship', ['championship' => $product->championship->title]) }}</li>
                            @endif
                        </ul>
                    </section>
                @endif
            </div>

            <aside class="space-y-4">
                <div class="rounded-md border border-zinc-200 bg-white p-4 space-y-3">
                    <h3 class="text-sm text-zinc-500">{{ __('Price') }}</h3>

                    @if ($currentPrice === null)
                        <p class="text-zinc-700">{{ __('Not available at the moment') }}</p>
                    @else
                        <p class="text-2xl font-mono tabular-nums">{{ $currentPrice->label() }}</p>
                        @if ($currentPrice->isRange())
                            <p class="text-sm text-zinc-600">{{ __('final price after review') }}. {{ __('The organizer confirms the amount once your request is reviewed.') }}</p>
                        @endif
                        @if ($currentPrice->ends_at)
                            <p class="text-sm text-zinc-600">{{ __('This price applies until :date.', ['date' => $currentPrice->ends_at->setTimezone(config('races.timezone'))->isoFormat('D MMMM YYYY')]) }}</p>
                        @endif

                        @if ($product->enrollmentChampionship() && ! $product->enrollmentChampionship()->enrollment->enabled)
                            <p class="text-zinc-700">{{ __('Not available at the moment') }}</p>
                        @elseif ($product->enrollmentChampionship())
                            <x-button-link href="{{ route('public.championships.enrollment', $product->enrollmentChampionship()) }}" class="w-full">{{ __('Order it when enrolling') }}</x-button-link>
                        @elseif ($user?->hasVerifiedEmail())
                            <form method="POST" action="{{ route('public.products.order', $product) }}" class="space-y-3">
                                @csrf
                                @if ($product->needsLicenceWhenOrdered())
                                    @if ($licences->isEmpty())
                                        <p class="text-sm text-zinc-700">
                                            {{ __('The discounts are for a driver licence valid in the championship year, and you have none.') }}
                                            <a href="{{ route('my-licences.index') }}" class="text-orange-600 hover:text-orange-900">{{ __('Add or request a licence') }}</a>
                                        </p>
                                    @else
                                        <div>
                                            <x-label for="licence" value="{{ __('For the licence') }}" />
                                            <select id="licence" name="licence" class="mt-1 w-full rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50">
                                                @foreach ($licences as $licence)
                                                    <option value="{{ $licence->ulid }}" @selected(old('licence') === $licence->ulid)>{{ $licence->holder->fullName() }} &middot; {{ $licence->number }}</option>
                                                @endforeach
                                            </select>
                                            <x-input-error for="licence" class="mt-2" />
                                        </div>
                                    @endif
                                @endif
                                <x-button type="submit" class="w-full">{{ __('Order') }}</x-button>
                            </form>
                        @elseif ($user)
                            <p class="text-sm text-zinc-700">{{ __('Verify your email address to order.') }}</p>
                            <x-button-link href="{{ route('public.products.continue', $product) }}" class="w-full">{{ __('Verify email address') }}</x-button-link>
                        @else
                            <div class="flex flex-col gap-2">
                                <x-button-link href="{{ route('public.products.continue', $product) }}">{{ __('Sign in to order') }}</x-button-link>
                                @if (Route::has('register'))
                                    <x-secondary-button-link href="{{ route('public.products.register', $product) }}">{{ __('Create an account') }}</x-secondary-button-link>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>

                @guest
                    <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-700 space-y-2">
                        <h3 class="font-semibold text-zinc-800">{{ __('Why an account is needed') }}</h3>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>{{ __('Your request is saved as a draft, so you can complete it later.') }}</li>
                            <li>{{ __('Progress and price updates are emailed to your account.') }}</li>
                            <li>{{ __('Your licences and race numbers stay available for future race registrations.') }}</li>
                        </ul>
                    </div>
                @endguest
            </aside>
        </div>
    </div>
</x-app-layout>
