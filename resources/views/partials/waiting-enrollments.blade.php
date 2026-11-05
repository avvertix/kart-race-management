{{-- The Enrollments waiting for a Licence Request, linked to their page. Expects $licenceRequest. --}}
@php
    $waitingEnrollments = $licenceRequest->enrollments()->active()->with('championship')->get();
@endphp
@if ($waitingEnrollments->isNotEmpty())
    <div class="rounded-md border border-sky-200 bg-sky-50 p-3 text-sm text-sky-900 space-y-1">
        @foreach ($waitingEnrollments as $waitingEnrollment)
            <p>
                {{ __('The enrollment in :championship waits for this request.', ['championship' => $waitingEnrollment->championship->title]) }}
                <a href="{{ route('my-enrollments.show', $waitingEnrollment) }}" class="font-medium underline">{{ __('View the enrollment') }}</a>
            </p>
        @endforeach
    </div>
@endif
