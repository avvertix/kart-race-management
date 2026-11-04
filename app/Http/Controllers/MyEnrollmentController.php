<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Enrollments\WithdrawEnrollment;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;

class MyEnrollmentController extends Controller
{
    /**
     * The driver withdraws their Enrollment while it is submitted.
     */
    public function withdraw(Enrollment $enrollment, WithdrawEnrollment $withdrawEnrollment): RedirectResponse
    {
        $this->authorize('withdraw', $enrollment);

        $withdrawEnrollment($enrollment, byOrganizer: false);

        return to_route('public.championships.enrollment', $enrollment->championship)
            ->with('flash.banner', __('Enrollment withdrawn.'));
    }
}
