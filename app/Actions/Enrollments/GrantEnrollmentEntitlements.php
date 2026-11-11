<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\EntitlementMode;
use App\Models\Enrollment;
use App\Models\Entitlement;
use Illuminate\Support\Facades\DB;

class GrantEnrollmentEntitlements
{
    /**
     * Make the race fee discount of the Enrollment's Licence follow its option, once the Enrollment is active, its
     * deposit paid (or its full amount, or nothing due) and its Licence verified or emitted; until then it grants
     * nothing. The discount of the option's kind is created, or brought to the option's amount keeping what was used;
     * those of another kind, left by an earlier option, are cancelled where unused. Runs on submitting, on payment
     * confirmation, on licence verification and emission, on a change of licence or option, and when an Enrollment
     * comes back. Running twice changes nothing: the Enrollment row is locked while its discounts are looked up.
     */
    public function __invoke(Enrollment $enrollment): void
    {
        DB::transaction(function () use ($enrollment) {
            $locked = Enrollment::query()->whereKey($enrollment->getKey())->with(['option', 'licence'])->lockForUpdate()->first();

            if ($locked === null || ! $locked->status->isActive() || ! $locked->isDepositPaid()) {
                return;
            }

            $option = $locked->option;
            $mode = $option?->entitlement_mode;
            $licence = $locked->licence;

            $granted = Entitlement::query()
                ->where('source_type', $locked->getMorphClass())
                ->where('source_id', $locked->getKey())
                ->lockForUpdate()
                ->get();

            $granted->filter(fn (Entitlement $entitlement) => $entitlement->mode !== $mode)
                ->each(fn (Entitlement $entitlement) => $entitlement->update(['remaining' => 0]));

            if ($option === null || $mode === null || $licence === null || ! $licence->isVerifiedOrEmitted()) {
                return;
            }

            $amount = (int) $option->entitlement_amount;
            $current = $granted->firstWhere('mode', $mode);

            if ($current === null) {
                Entitlement::query()->create([
                    'source_type' => $locked->getMorphClass(),
                    'source_id' => $locked->getKey(),
                    'mode' => $mode,
                    'championship_id' => $locked->championship_id,
                    'licence_id' => $licence->getKey(),
                    'licence_hash' => $licence->number_hash,
                    'amount' => $amount,
                    'remaining' => $amount,
                ]);

                return;
            }

            $current->update(['amount' => $amount, 'remaining' => max(0, $amount - self::used($current))]);
        });
    }

    /**
     * What the discount already gave: the race fees discounted with credits, or the cents taken from a balance.
     */
    private static function used(Entitlement $entitlement): int
    {
        $uses = DB::table('participant_entitlement')->where('entitlement_id', $entitlement->getKey());

        return $entitlement->mode === EntitlementMode::Credit ? $uses->count() : (int) $uses->sum('amount');
    }
}
