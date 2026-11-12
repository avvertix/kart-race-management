<x-app-layout>
    <x-slot name="title">
        {{ __('Dashboard') }}
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 grid md:grid-cols-12 gap-8">

        <x-welcome-new-user class="md:col-span-12" />

        @feature(\App\Features\LicencesAndEnrollments::class)
            <x-driver-enrollments class="md:col-span-12" />

            @can('create', \App\Models\Enrollment::class)
                <x-promo-code-find class="md:col-span-12" />
            @endcan
        @endfeature

        @can('viewAny', \App\Models\Race::class)
            <x-next-races class="md:col-span-7 lg:col-span-8" />
        @else
            <x-next-races-for-drivers class="md:col-span-7 lg:col-span-8" />
        @endcan

        <x-championships-list class="md:col-span-5 lg:col-span-4" />

    </div>

</x-app-layout>
