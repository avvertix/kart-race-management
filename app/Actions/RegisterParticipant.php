<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\ParticipantRegistered;
use App\Listeners\ApplyBonusToParticipant;
use App\Listeners\CheckParticipantForWildcard;
use App\Models\Category;
use App\Models\CompetitorLicence;
use App\Models\DriverLicence;
use App\Models\Race;
use App\Models\Sex;
use App\Models\User;
use App\Validation\ParticipantValidationRules;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Pipeline;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RegisterParticipant
{
    use ParticipantValidationRules;

    /**
     * Creates a new race participant.
     *
     * @param  User|null  $user  The user that is performing the operation
     * @return \App\Models\Participant
     */
    public function __invoke(Race $race, array $input, ?User $user = null)
    {

        if ($race->isCancelled()) {
            throw ValidationException::withMessages([
                'bib' => __('The race has been cancelled and registration is now closed.'),
            ]);
        }

        $validatedInput = Validator::make($input, [
            ...$this->getBibValidationRules(),
            ...$this->getCategoryValidationRules((int) $race->championship_id),
            ...$this->getDriverValidationRules(),
            ...$this->getCompetitorValidationRules(),
            ...$this->getMechanicValidationRules(),
            ...$this->getVehicleValidationRules(),

            'consent_privacy' => ['sometimes', 'required', 'accepted'],
        ])->validate();

        $category = Category::whereUlid($validatedInput['category'])->firstOrFail();

        try {

            $race->loadCount('participants');

            $licenceHash = hash('sha512', $validatedInput['driver_licence_number']);

            $participant = Cache::lock($this->getLockKey($race, $validatedInput['bib']), 10)
                ->block(5, function () use ($race, $validatedInput, $user, $category, $licenceHash) {

                    // Check if participants is below race limit

                    if ($race->hasTotalParticipantLimit() && ($race->participants_count + 1) > $race->getTotalParticipantLimit()) {
                        throw ValidationException::withMessages([
                            'participants_limit' => __('We reached the maximum allowed participants to this race.'),
                        ]);
                    }

                    $this->ensureDriverUsesUniqueOrAssignedBib([
                        'driver_first_name' => $validatedInput['driver_first_name'],
                        'driver_last_name' => $validatedInput['driver_last_name'],
                        'bib' => $validatedInput['bib'],
                        'driver_licence_number' => $licenceHash,
                    ], $race, $user);

                    $participant = $race->participants()->create([
                        'bib' => $validatedInput['bib'],
                        'category' => $validatedInput['category'],
                        'category_id' => $category->getKey(),
                        'first_name' => $validatedInput['driver_first_name'],
                        'last_name' => $validatedInput['driver_last_name'],
                        'added_by' => $user?->getKey(),
                        'championship_id' => $race->championship_id,
                        'driver_licence' => $licenceHash,
                        'competitor_licence' => isset($validatedInput['competitor_licence_number']) ? hash('sha512', $validatedInput['competitor_licence_number']) : null,
                        'licence_type' => $validatedInput['driver_licence_type'] ?? DriverLicence::LOCAL_NATIONAL->value,
                        'driver' => [
                            'first_name' => $validatedInput['driver_first_name'],
                            'last_name' => $validatedInput['driver_last_name'],
                            'licence_type' => $validatedInput['driver_licence_type'] ?? DriverLicence::LOCAL_NATIONAL->value,
                            'licence_number' => $validatedInput['driver_licence_number'],
                            'fiscal_code' => $validatedInput['driver_fiscal_code'],
                            'licence_renewed_at' => Date::normalizeToDateString($validatedInput['driver_licence_renewed_at'] ?? null),
                            'nationality' => $validatedInput['driver_nationality'],
                            'email' => $validatedInput['driver_email'],
                            'phone' => $validatedInput['driver_phone'],
                            'birth_date' => Date::normalizeToDateString($validatedInput['driver_birth_date']),
                            'birth_place' => $validatedInput['driver_birth_place'],
                            'medical_certificate_expiration_date' => Date::normalizeToDateString($validatedInput['driver_medical_certificate_expiration_date'] ?? null),
                            'residence_address' => $this->processAddressInput($validatedInput, 'driver_residence'),
                            'sex' => $validatedInput['driver_sex'] ?? Sex::UNSPECIFIED,
                        ],
                        'competitor' => isset($validatedInput['competitor_licence_number']) ? [
                            'first_name' => $validatedInput['competitor_first_name'],
                            'last_name' => $validatedInput['competitor_last_name'],
                            'licence_type' => $validatedInput['competitor_licence_type'] ?? CompetitorLicence::LOCAL->value,
                            'licence_number' => $validatedInput['competitor_licence_number'],
                            'fiscal_code' => $validatedInput['competitor_fiscal_code'],
                            'licence_renewed_at' => Date::normalizeToDateString($validatedInput['competitor_licence_renewed_at'] ?? null),
                            'nationality' => $validatedInput['competitor_nationality'],
                            'email' => $validatedInput['competitor_email'],
                            'phone' => $validatedInput['competitor_phone'],
                            'birth_date' => Date::normalizeToDateString($validatedInput['competitor_birth_date']),
                            'birth_place' => $validatedInput['competitor_birth_place'],
                            'residence_address' => $this->processAddressInput($validatedInput, 'competitor_residence'),
                        ] : null,
                        'mechanic' => isset($validatedInput['mechanic_name']) && isset($validatedInput['mechanic_licence_number']) ? [
                            'name' => $validatedInput['mechanic_name'],
                            'licence_number' => $validatedInput['mechanic_licence_number'],
                        ] : null,
                        'vehicles' => $this->processVehicle($validatedInput),
                        'consents' => [
                            'privacy' => ($validatedInput['consent_privacy'] ?? false) ? true : false,
                        ],
                        'use_bonus' => false,
                        'locale' => App::currentLocale(),
                    ]);

                    return $participant;
                });

            Pipeline::send(new ParticipantRegistered($participant, $race))
                ->through([
                    ApplyBonusToParticipant::class,
                    CalculateParticipationCost::class,
                    CheckParticipantForWildcard::class,
                ])
                ->thenReturn();

            $participant->sendConfirmParticipantNotification();

            return $participant;

        } catch (LockTimeoutException $th) {
            throw ValidationException::withMessages([
                'bib' => __('The race number has already been taken.'),
            ]);
        }
    }

    protected function getLockKey(Race $race, $seed)
    {
        if ($race->hasTotalParticipantLimit()) {
            return "participant:{$race->uuid}";
        }

        return "participant:{$seed}";
    }
}
