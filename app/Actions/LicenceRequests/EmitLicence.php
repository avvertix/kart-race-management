<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceRequestStatus;
use App\Enums\LicenceSource;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\User;
use App\Notifications\LicenceEmitted;
use App\Rules\LicenseNumberValidationRule;
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
     * The Enrollments waiting for the request move to the new Licence.
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

                Enrollment::query()
                    ->where('licence_request_id', $locked->getKey())
                    ->active()
                    ->update([
                        'licence_id' => $licence->getKey(),
                        'licence_request_id' => null,
                    ]);

                return $licence;
            });
        } catch (UniqueConstraintViolationException) {
            Storage::disk('licence-documents')->delete($path);

            throw ValidationException::withMessages(['number' => __('This licence is already registered for :year.', ['year' => $validityYear])]);
        } catch (Throwable $exception) {
            Storage::disk('licence-documents')->delete($path);

            throw $exception;
        }

        $licence->user->notify(new LicenceEmitted($licence));

        return $licence;
    }
}
