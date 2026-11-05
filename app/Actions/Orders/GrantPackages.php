<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\EnrollmentStatus;
use App\Enums\LicenceRequestStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductGrantKind;
use App\Models\Enrollment;
use App\Models\Entitlement;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\ProductGrant;
use Illuminate\Support\Facades\DB;

class GrantPackages
{
    /**
     * Create the Entitlements of the Packages in a paid Order, for the Licence they are for: the one emitted from the
     * request the Order covers, or the one of the Enrollment the Order pays. Runs when the Order is paid and when that
     * Licence is emitted, whichever comes last; until both happened it grants nothing. Granting twice creates nothing
     * twice, also when payment and emission end at the same moment: the Order is locked. A rejected request or Enrollment
     * grants nothing, and neither does a Licence of another year than the championship.
     */
    public function __invoke(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

            if ($locked === null || $locked->status !== OrderStatus::Paid) {
                return;
            }

            $licence = $this->licenceOf($locked);

            if ($licence === null) {
                return;
            }

            $locked->lines()->with('product.grants.championship')->get()->each(function (OrderLine $line) use ($licence) {
                $line->product->grants
                    ->where('kind', ProductGrantKind::Entitlement)
                    ->filter(fn (ProductGrant $grant) => $grant->championship !== null && $grant->championship->start_at->year === $licence->validity_year)
                    ->each(fn (ProductGrant $grant) => Entitlement::query()->firstOrCreate([
                        'product_grant_id' => $grant->getKey(),
                        'source_type' => $line->getMorphClass(),
                        'source_id' => $line->getKey(),
                    ], [
                        'championship_id' => $grant->championship_id,
                        'licence_id' => $licence->getKey(),
                        'licence_hash' => $licence->number_hash,
                        'mode' => $grant->mode,
                        'amount' => (int) $grant->amount,
                        'remaining' => (int) $grant->amount,
                    ]));
            });
        });
    }

    /**
     * The Licence the Packages of the Order are for, null while it is not known yet: the one chosen when ordering, emitted
     * from the request the Order covers, or of the Enrollment the Order pays.
     */
    private function licenceOf(Order $order): ?Licence
    {
        if ($order->licence_id !== null) {
            return $order->licence;
        }

        $licenceRequest = LicenceRequest::query()->where('covering_order_id', $order->getKey())->where('status', '!=', LicenceRequestStatus::Rejected)->latest('id')->first();

        if ($licenceRequest !== null) {
            return $licenceRequest->status === LicenceRequestStatus::Emitted ? $licenceRequest->licence : null;
        }

        return Enrollment::query()
            ->where('order_id', $order->getKey())
            ->whereIn('status', EnrollmentStatus::active())
            ->whereNotNull('licence_id')
            ->first()
            ?->licence;
    }
}
