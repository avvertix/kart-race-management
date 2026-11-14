<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceRequestSection;
use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Models\LicenceRequestChangeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FlagLicenceRequestSection
{
    /**
     * Flag a section with a note for the requester, e.g. a wrong birth place. The flag reaches the requester once the
     * organizer asks for changes.
     *
     * @throws ValidationException when the request is not being reviewed, the section is not part of it or the note is missing
     */
    public function __invoke(LicenceRequest $licenceRequest, User $organizer, LicenceRequestSection $section, string $note): LicenceRequestChangeRequest
    {
        Validator::make(['section' => $section->value, 'note' => $note], [
            'section' => ['required', Rule::in(array_map(fn (LicenceRequestSection $section) => $section->value, $licenceRequest->sections()))],
            'note' => 'required|string|max:1000',
        ])->validate();

        return DB::transaction(function () use ($licenceRequest, $organizer, $section, $note) {
            $locked = $licenceRequest->lockForReview(
                LicenceRequestStatus::underReview(),
                $organizer,
                __('Sections can be flagged only while the request waits for the organizer.'),
            );

            return $locked->changeRequests()->create([
                'section' => $section,
                'note' => $note,
            ]);
        });
    }
}
