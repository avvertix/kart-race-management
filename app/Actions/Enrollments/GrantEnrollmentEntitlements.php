<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Models\Enrollment;
use App\Models\Entitlement;
use Illuminate\Support\Facades\DB;

class GrantEnrollmentEntitlements
{
    /**
     * Create the race fee discount of the Enrollment's option for its Licence, once the Enrollment is active, its
     * deposit paid (or its full amount, or nothing due) and its Licence verified or emitted; until then it grants
     * nothing. Runs on submitting, on payment confirmation, on licence verification and emission, on a change of licence, and on a change of option. Granting
     * twice creates nothing twice: the Enrollment row is locked while looking for the Entitlement it is the source of.
     */
    public function __invoke(Enrollment $enrollment): void
    {
        DB::transaction(function () use ($enrollment) {
            $locked = Enrollment::query()->whereKey($enrollment->getKey())->with(['option', 'licence'])->lockForUpdate()->first();

            $option = $locked?->option;
            $licence = $locked?->licence;

            if ($locked === null || $option === null || $option->entitlement_mode === null || $licence === null || ! $licence->isVerifiedOrEmitted()) {
                return;
            }

            if (! $locked->status->isActive() || ! $locked->isDepositPaid()) {
                return;
            }

            Entitlement::query()->firstOrCreate([
                'source_type' => $locked->getMorphClass(),
                'source_id' => $locked->getKey(),
            ], [
                'championship_id' => $locked->championship_id,
                'licence_id' => $licence->getKey(),
                'licence_hash' => $licence->number_hash,
                'mode' => $option->entitlement_mode,
                'amount' => (int) $option->entitlement_amount,
                'remaining' => (int) $option->entitlement_amount,
            ]);
        });
    }
}
