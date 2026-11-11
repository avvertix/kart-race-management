<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\EntitlementMode;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Entitlement;
use App\Models\Licence;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreditEnrollmentPayment
{
    /**
     * Turn what a withdrawn Enrollment paid into a race fee balance on its Licence, e.g. a deposit whose balance was never
     * paid. What was paid beyond the amount is left out, as it is refunded. The race fee discount the option already
     * granted is cancelled where unused, and the race fees it already covered are taken off the balance. The Enrollment
     * can't come back afterwards.
     *
     * @throws ValidationException when the Enrollment is not withdrawn, has no Licence, nothing paid, is already credited, or the championship does not use race fee discounts
     */
    public function __invoke(Enrollment $enrollment): ?Entitlement
    {
        return DB::transaction(function () use ($enrollment) {
            $championship = Championship::query()->whereKey($enrollment->championship_id)->lockForUpdate()->firstOrFail();

            $locked = Enrollment::query()->whereKey($enrollment->getKey())->with('licence')->lockForUpdate()->firstOrFail();

            if (! $locked->canBeCredited()) {
                throw ValidationException::withMessages(['credit' => __('Only what a withdrawn enrollment with a licence paid can become race credit, once.')]);
            }

            if (! $championship->entitlements_enabled) {
                throw ValidationException::withMessages(['credit' => __('The championship does not use race fee discounts.')]);
            }

            $granted = Entitlement::query()
                ->where('source_type', $locked->getMorphClass())
                ->where('source_id', $locked->getKey())
                ->lockForUpdate()
                ->get();

            $alreadyDiscounted = (int) DB::table('participant_entitlement')->whereIn('entitlement_id', $granted->modelKeys())->sum('amount');

            $granted->each(fn (Entitlement $entitlement) => $entitlement->update(['remaining' => 0]));

            $locked->update(['credited_at' => now()]);

            $balance = min($locked->paidAmount(), $locked->amount) - $alreadyDiscounted;

            if ($balance <= 0) {
                return null;
            }

            /** @var Licence $licence */
            $licence = $locked->licence;

            return Entitlement::query()->create([
                'championship_id' => $locked->championship_id,
                'licence_id' => $licence->getKey(),
                'licence_hash' => $licence->number_hash,
                'mode' => EntitlementMode::Balance,
                'amount' => $balance,
                'remaining' => $balance,
                'source_type' => $locked->getMorphClass(),
                'source_id' => $locked->getKey(),
            ]);
        });
    }
}
