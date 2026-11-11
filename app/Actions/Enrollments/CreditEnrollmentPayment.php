<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Actions\PromoCodes\IssuePromoCodes;
use App\Enums\EntitlementMode;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Entitlement;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreditEnrollmentPayment
{
    public function __construct(private IssuePromoCodes $issuePromoCodes) {}

    /**
     * Turn what a withdrawn or rejected Enrollment paid into a race fee balance promo code the organizer hands to the
     * driver, e.g. a deposit whose balance was never paid. What was paid beyond the amount is left out, as it is
     * refunded; the price of a licence renewal the option paid and that was emitted is taken off, in cents, as the licence
     * was renewed; and so are the race fees the option's discount already covered, while its unused part is cancelled.
     * The Enrollment can't come back afterwards. Nothing is issued when nothing is left.
     *
     * @throws ValidationException when the Enrollment can't be credited, or the championship does not use race fee discounts
     */
    public function __invoke(Enrollment $enrollment, User $organizer, int $renewalDeduction = 0): ?PromoCode
    {
        Validator::make(['renewal_deduction' => $renewalDeduction], ['renewal_deduction' => 'integer|min:0'])->validate();

        return DB::transaction(function () use ($enrollment, $organizer, $renewalDeduction) {
            $championship = Championship::query()->whereKey($enrollment->championship_id)->lockForUpdate()->firstOrFail();

            $locked = Enrollment::query()->whereKey($enrollment->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->canBeCredited()) {
                throw ValidationException::withMessages(['credit' => __('Only what a withdrawn or rejected enrollment paid can become a promo code, once.')]);
            }

            if ($renewalDeduction > 0 && $locked->coveredEmittedRenewal() === null) {
                throw ValidationException::withMessages(['renewal_deduction' => __('The enrollment paid no licence renewal.')]);
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

            $credit = min($locked->paidAmount(), $locked->amount) - $renewalDeduction - $alreadyDiscounted;

            $promoCode = $credit > 0
                ? ($this->issuePromoCodes)($championship, $organizer, EntitlementMode::Balance, $credit)->sole()
                : null;

            $locked->update([
                'credited_at' => now(),
                'credit_promo_code_id' => $promoCode?->getKey(),
            ]);

            return $promoCode;
        });
    }
}
