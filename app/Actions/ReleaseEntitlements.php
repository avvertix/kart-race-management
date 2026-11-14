<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Entitlement;
use App\Models\Participant;
use Illuminate\Support\Facades\DB;

class ReleaseEntitlements
{
    /**
     * Give back what the registration used of its Entitlements: a credit for each race fee covered, or the discounted
     * amount for a balance. Run when the registration is deleted, its race cancelled, or its fee or licence number change.
     */
    public function __invoke(Participant $participant): void
    {
        DB::transaction(function () use ($participant) {
            $used = DB::table('participant_entitlement')->where('participant_id', $participant->getKey())->get();

            foreach ($used as $usage) {
                $entitlement = Entitlement::query()->whereKey($usage->entitlement_id)->lockForUpdate()->first();

                $entitlement?->giveBack((int) $usage->amount);
            }

            DB::table('participant_entitlement')->where('participant_id', $participant->getKey())->delete();
        });
    }
}
