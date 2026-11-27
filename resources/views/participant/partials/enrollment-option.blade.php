{{-- The Enrollment Option of the driver's active Enrollment in the championship, e.g. what a Gold enrollment includes at the track. Links to the Enrollment for those reviewing enrollments. Expects $enrollment, null when not enrolled, and $canOpenEnrollments, see Enrollment::isReviewableBy(). --}}
@if ($enrollment?->option)
    @if ($canOpenEnrollments)
        <a href="{{ route('enrollments.show', $enrollment) }}" class="inline-flex items-center rounded-md bg-orange-50 px-2 py-1 text-xs font-medium text-orange-800 ring-1 ring-inset ring-orange-600/20 hover:bg-orange-100" title="{{ __('Enrollment option') }}">{{ $enrollment->option->title }}</a>
    @else
        <span class="inline-flex items-center rounded-md bg-orange-50 px-2 py-1 text-xs font-medium text-orange-800 ring-1 ring-inset ring-orange-600/20" title="{{ __('Enrollment option') }}">{{ $enrollment->option->title }}</span>
    @endif
@endif
