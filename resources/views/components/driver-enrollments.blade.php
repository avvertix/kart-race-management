<div {{ $attributes->merge(['class' => 'grid gap-8 md:grid-cols-2']) }}>
    @if ($openChampionships->isNotEmpty())
        <section class="space-y-3">
            <p data-section="true" class="font-mono text-xs/6 font-medium tracking-widest text-gray-600 uppercase">{{ __('Open for enrollment') }}</p>

            <div class="-mx-2 sm:mx-0 space-y-3">
                @foreach ($openChampionships as $championship)
                    <article class="p-2 md:p-4 bg-white rounded-sm flex flex-wrap items-center justify-between gap-3">
                        <div class="space-y-1">
                            <h2 class="font-bold">{{ $championship->title }}</h2>
                            <p class="text-sm text-zinc-700">{{ __('Enrollment open until :date', ['date' => $championship->enrollment->closes_at->setTimezone($timezone)->isoFormat('D MMMM YYYY')]) }}</p>
                        </div>
                        <a href="{{ route('public.championships.enrollment', $championship) }}"
                            class="font-medium px-3 py-2 rounded-sm ring-0 hover:ring-2 focus-visible:ring-2 ring-orange-500 bg-zinc-100 hover:bg-orange-100">
                            {{ __('Enroll') }}
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if ($enrollments->isNotEmpty())
        <section class="space-y-3">
            <p data-section="true" class="font-mono text-xs/6 font-medium tracking-widest text-gray-600 uppercase">{{ __('Your enrollments') }}</p>

            <ul class="-mx-2 sm:mx-0 space-y-3">
                @foreach ($enrollments as $enrollment)
                    <li class="p-2 md:p-4 bg-white rounded-sm group relative ring-0 hover:ring-2 focus-within:ring-2 ring-orange-500 space-y-1">
                        <a href="{{ route('my-enrollments.show', $enrollment) }}" class="font-bold group-hover:text-orange-900 focus:outline-hidden">
                            <span class="z-10 absolute inset-0"></span>{{ $enrollment->championship->title }}
                        </a>
                        <p class="text-sm text-zinc-700">
                            {{ $enrollment->holder()?->fullName() }}
                            &middot;
                            @if (! $enrollment->status->isActive() || $enrollment->status === \App\Enums\EnrollmentStatus::Confirmed)
                                {{ $enrollment->status->label() }}
                            @elseif ($enrollment->needsLicence())
                                {{ __('Choose another licence') }}
                            @elseif (! $enrollment->payment_status->isSettled())
                                {{ __('Payment') }}: {{ $enrollment->payment_status->label() }}
                            @elseif ($enrollment->licence === null)
                                {{ __('Waiting for the licence request') }}
                            @else
                                {{ __('Waiting for the organizer to confirm') }}
                            @endif
                        </p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
