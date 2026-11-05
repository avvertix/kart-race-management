<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Actions\Enrollments\GrantEnrollmentEntitlements;
use App\Actions\Enrollments\RejectEnrollment;
use App\Actions\Orders\GrantPackages;
use App\Enums\EnrollmentStatus;
use App\Enums\LicenceRequestStatus;
use App\Enums\LicenceSource;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Entitlement;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Order;
use App\Models\User;
use App\Notifications\LicenceEmitted;
use App\Rules\LicenseNumberValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmitLicence
{
    /**
     * Create the Licence the provider emitted for a verified request, paid or covered by a package, with its temporary licence
     * PDF. The Licence takes the request's holder, Licence Type and competitor licence, and is managed by the requester.
     * The Enrollments waiting for the request move to the new Licence, and their race numbers are bound to it. Paid
     * Packages of the request and of those Enrollments are granted. Enrollments waiting in a championship of another year
     * refuse the Licence, and those of a Driver already enrolled there are rejected.
     *
     * @throws ValidationException when the request is not verified or paid, the input is not valid or the number is already registered
     */
    public function __invoke(LicenceRequest $licenceRequest, User $organizer, string $number, int $validityYear, UploadedFile $temporaryLicence): Licence
    {
        Validator::make([
            'number' => $number,
            'validity_year' => $validityYear,
            'temporary_licence' => $temporaryLicence,
        ], [
            'number' => ['required', 'string', 'max:250', new LicenseNumberValidationRule],
            'validity_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'temporary_licence' => ['required', File::types(['pdf'])->max(10 * 1024)],
        ])->validate();

        $licenceType = $licenceRequest->licenceTypeData();

        if ($licenceType === null) {
            throw ValidationException::withMessages(['request' => __('The Licence Type of the request is no longer listed.')]);
        }

        $path = $temporaryLicence->store('temporary-licences', 'licence-documents');

        if ($path === false) {
            throw ValidationException::withMessages(['temporary_licence' => __('The file could not be saved, please try again.')]);
        }

        try {
            $licence = DB::transaction(function () use ($licenceRequest, $organizer, $licenceType, $number, $validityYear, $path) {
                $locked = $licenceRequest->lockForReview(LicenceRequestStatus::verified(), $organizer, __('Only a verified request can be emitted.'));

                if (! $locked->isPaymentSettled()) {
                    throw ValidationException::withMessages(['request' => __('Confirm the payment before emitting the licence.')]);
                }

                $waiting = Enrollment::query()
                    ->where('licence_request_id', $locked->getKey())
                    ->active()
                    ->get();

                $championships = Championship::query()
                    ->whereKey($waiting->pluck('championship_id')->unique()->all())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $otherYear = $championships->first(fn (Championship $championship) => $championship->start_at->year !== $validityYear);

                if ($otherYear !== null) {
                    throw ValidationException::withMessages(['validity_year' => __('Enrollments wait for this request in a championship of :year.', ['year' => $otherYear->start_at->year])]);
                }

                $isRegistered = Licence::query()
                    ->where('number_hash', Licence::hashNumber($number))
                    ->where('validity_year', $validityYear)
                    ->where('provider', $locked->provider)
                    ->exists();

                if ($isRegistered) {
                    throw ValidationException::withMessages(['number' => __('This licence is already registered for :year.', ['year' => $validityYear])]);
                }

                $licence = Licence::create([
                    ...$licenceType->licenceAttributes($locked->includes_competitor),
                    'user_id' => $locked->user_id,
                    'number' => $number,
                    'validity_year' => $validityYear,
                    'holder' => $locked->holder,
                    'source' => LicenceSource::Emitted,
                    'temporary_licence_path' => $path,
                    'licence_request_id' => $locked->getKey(),
                ]);

                $locked->update([
                    'status' => LicenceRequestStatus::Emitted,
                    'emitted_at' => now(),
                    'licence_id' => $licence->getKey(),
                ]);

                Entitlement::linkTo($licence);

                Enrollment::query()->whereKey($waiting->modelKeys())->update([
                    'licence_id' => $licence->getKey(),
                    'licence_request_id' => null,
                ]);

                BibReservation::query()
                    ->whereIn('enrollment_id', $waiting->modelKeys())
                    ->notReleased()
                    ->get()
                    ->each(fn (BibReservation $reservation) => $reservation->update([
                        'licence_id' => $licence->getKey(),
                        'driver_licence' => $licence->number,
                        'driver_licence_hash' => $licence->number_hash,
                        'licence_type' => $licence->race_licence_category,
                    ]));

                $this->rejectDuplicates($licence, $waiting, $championships);

                return $licence;
            });
        } catch (UniqueConstraintViolationException) {
            Storage::disk('licence-documents')->delete($path);

            throw ValidationException::withMessages(['number' => __('This licence is already registered for :year.', ['year' => $validityYear])]);
        } catch (Throwable $exception) {
            Storage::disk('licence-documents')->delete($path);

            throw $exception;
        }

        $this->grantPackagesOf($licence);

        $licence->user->notify(new LicenceEmitted($licence));

        return $licence;
    }

    /**
     * Reject the Enrollments that moved to the Licence while its Driver is already enrolled in the same championship.
     * Call it inside the transaction, with the championships locked.
     *
     * @param  Collection<int, Enrollment>  $moved
     * @param  Collection<int, Championship>  $championships
     */
    private function rejectDuplicates(Licence $licence, Collection $moved, Collection $championships): void
    {
        $moved
            ->filter(fn (Enrollment $enrollment) => $enrollment->status === EnrollmentStatus::Submitted)
            ->filter(fn (Enrollment $enrollment) => Enrollment::isDriverEnrolled($championships->find($enrollment->championship_id), $licence->number_hash, $licence->holder, except: $enrollment))
            ->each(fn (Enrollment $enrollment) => app(RejectEnrollment::class)($enrollment, __('The driver is already enrolled in the championship.')));
    }

    /**
     * Grant the paid Packages waiting for the Licence, the one covering its request and those paying the active
     * Enrollments that moved to it, and the race fee discounts of those Enrollments' options.
     */
    private function grantPackagesOf(Licence $licence): void
    {
        $grantPackages = app(GrantPackages::class);

        $orders = Order::query()
            ->whereIn('id', LicenceRequest::query()->whereKey($licence->licence_request_id)->whereNotNull('covering_order_id')->select('covering_order_id'))
            ->orWhereIn('id', Enrollment::query()->where('licence_id', $licence->getKey())->active()->whereNotNull('order_id')->select('order_id'))
            ->get();

        $orders->each(fn (Order $order) => $grantPackages($order));

        $grantEnrollmentEntitlements = app(GrantEnrollmentEntitlements::class);

        Enrollment::query()->where('licence_id', $licence->getKey())->active()->get()
            ->each(fn (Enrollment $enrollment) => $grantEnrollmentEntitlements($enrollment));
    }
}
