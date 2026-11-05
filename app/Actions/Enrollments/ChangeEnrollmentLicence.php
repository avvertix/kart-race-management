<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Actions\LicenceRequests\StartLicenceRenewal;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ChangeEnrollmentLicence
{
    public function __construct(private StartLicenceRenewal $startLicenceRenewal) {}

    /**
     * Give a submitted Enrollment whose Licence Request was rejected another Licence: one of the User, an open request,
     * or a renewal started now for the championship's year. The Enrollment keeps its Order and race number; an Order
     * paying a renewal (a Package including one, or a fee covering it) that no other request uses covers the new or
     * chosen request. A Licence already holding a race number in the championship can't take one more.
     *
     * @param  array{licence?: ?string, licence_request?: ?string, renew?: ?string}  $input  ULIDs, or `new` to renew a licence not on the platform
     *
     * @throws ValidationException
     */
    public function __invoke(Enrollment $enrollment, User $user, array $input): Enrollment
    {
        $championship = $enrollment->championship;

        $validated = Validator::make($input, SubmitEnrollment::subjectRules($user, $championship))->validate();

        return DB::transaction(function () use ($enrollment, $user, $championship, $validated) {
            Championship::query()->whereKey($championship->getKey())->lockForUpdate()->firstOrFail();

            $locked = $enrollment->lockInStatus([EnrollmentStatus::Submitted], 'enrollment', __('Only a submitted enrollment can change licence.'));

            if (! $locked->needsLicence()) {
                throw ValidationException::withMessages(['enrollment' => __('The enrollment still waits for its licence request.')]);
            }

            $order = $locked->order_id === null ? null : Order::query()->whereKey($locked->order_id)->lockForUpdate()->first();
            $renewalOrder = $order !== null && $order->status !== OrderStatus::Cancelled && $order->hasUnusedRenewal() ? $order : null;

            $licence = Licence::findByUlid($validated['licence'] ?? null);
            $licenceRequest = null;

            if (isset($validated['licence_request'])) {
                $licenceRequest = LicenceRequest::query()->where('ulid', $validated['licence_request'])->enrollableIn($championship)->lockForUpdate()->first();

                if ($licenceRequest === null) {
                    throw ValidationException::withMessages(['licence_request' => __('The licence request is no longer open: enroll the licence instead.')]);
                }
            }

            if (filled($validated['renew'] ?? null)) {
                $licenceRequest = ($this->startLicenceRenewal)(
                    $user,
                    Licence::findByUlid($validated['renew'] === SubmitEnrollment::RENEW_NEW ? null : $validated['renew']),
                    $renewalOrder,
                    $championship->start_at->year,
                    $championship->licences->provider,
                );
            } elseif ($licenceRequest !== null && $renewalOrder !== null) {
                $licenceRequest->coverWith($renewalOrder);
            }

            Enrollment::ensureNotEnrolled($championship, $licence, $licenceRequest, except: $locked);

            $reservations = $locked->reservations()->notReleased()->get();

            if ($licence !== null && $reservations->isNotEmpty() && PickRaceNumber::reservationHeldBy($championship, $licence, $locked) !== null) {
                throw ValidationException::withMessages(['licence' => __('This licence already holds a race number in the championship.')]);
            }

            $locked->update([
                'licence_id' => $licence?->getKey(),
                'licence_request_id' => $licence === null ? $licenceRequest?->getKey() : null,
            ]);

            if ($licence !== null) {
                $reservations->each(fn (BibReservation $reservation) => $reservation->update([
                    'licence_id' => $licence->getKey(),
                    'driver' => $licence->holder->fullName(),
                    'driver_licence' => $licence->number,
                    'driver_licence_hash' => $licence->number_hash,
                    'licence_type' => $licence->race_licence_category,
                ]));
            }

            return $locked;
        });
    }
}
