<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\ProductGrantKind;
use App\Models\LicenceRequest;
use App\Models\ProductGrant;
use App\Notifications\EnrollmentNeedsAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollPackagedRequest
{
    public function __construct(private CreateEnrollment $createEnrollment) {}

    /**
     * When a Licence Request covered by a Package with Enrollment grants is started or submitted, enroll it in each
     * championship: a submitted Enrollment waiting for the request, paid by the Package's Order. A championship it is
     * already enrolled in is skipped; one not open to the request is skipped, logged and returned.
     *
     * @return array<int, string> Why an Enrollment couldn't be created, by championship
     */
    public function __invoke(LicenceRequest $licenceRequest): array
    {
        $order = $licenceRequest->coveringOrder;

        if ($order === null) {
            return [];
        }

        $skipped = [];

        $order->grantsOf(ProductGrantKind::Enrollment)->each(function (ProductGrant $grant) use ($licenceRequest, $order, &$skipped) {
            if ($grant->championship === null) {
                return;
            }

            if ($licenceRequest->enrollments()->active()->where('championship_id', $grant->championship_id)->exists()) {
                return;
            }

            try {
                DB::transaction(function () use ($grant, $licenceRequest, $order) {
                    $enrollment = ($this->createEnrollment)($licenceRequest->user, $grant->championship, null, $licenceRequest, null);

                    $enrollment->update(['order_id' => $order->getKey()]);

                    if ($enrollment->competitor_licence_id === null && $enrollment->requiresCompetitor()) {
                        $enrollment->user->notify(new EnrollmentNeedsAction($enrollment, needsLicence: false));
                    }
                });
            } catch (ValidationException $exception) {
                logs()->warning('Packaged enrollment skipped', [
                    'licence_request' => $licenceRequest->getKey(),
                    'championship' => $grant->championship_id,
                    'reason' => $exception->getMessage(),
                ]);

                $skipped[] = __('Not enrolled in :championship: :reason', ['championship' => $grant->championship->title, 'reason' => $exception->getMessage()]);
            }
        });

        return $skipped;
    }
}
