<?php

declare(strict_types=1);

namespace App\Actions\PromoCodes;

use App\Enums\EntitlementMode;
use App\Enums\PromoCodeStatus;
use App\Models\Championship;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class IssuePromoCodes
{
    /**
     * How many random codes are tried before giving up, when they are already taken.
     */
    private const ATTEMPTS = 10;

    /**
     * Issue `$count` unclaimed codes for the championship, random unless a custom code is given for a single one. Codes
     * are unique across the platform, ignoring case.
     *
     * @param  int  $amount  The number of race fees for credits, the cents for a balance
     * @return Collection<int, PromoCode>
     *
     * @throws ValidationException when the custom code is taken
     */
    public function __invoke(Championship $championship, User $organizer, EntitlementMode $mode, int $amount, int $count = 1, ?string $code = null): Collection
    {
        $attributes = [
            'championship_id' => $championship->getKey(),
            'mode' => $mode,
            'amount' => $amount,
            'status' => PromoCodeStatus::Unclaimed,
            'created_by' => $organizer->getKey(),
        ];

        if ($code !== null) {
            $code = PromoCode::normalize($code);

            try {
                return collect([PromoCode::create(['code' => $code, ...$attributes])]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['code' => __('The code :code is already in use.', ['code' => $code])]);
            }
        }

        return DB::transaction(fn () => collect(range(1, $count))->map(fn () => $this->issueRandom($attributes)));
    }

    /**
     * Create a code with a random value, trying another one when it is taken.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function issueRandom(array $attributes): PromoCode
    {
        for ($attempt = 1; $attempt <= self::ATTEMPTS; $attempt++) {
            try {
                return DB::transaction(fn () => PromoCode::create(['code' => PromoCode::randomCode(), ...$attributes]));
            } catch (UniqueConstraintViolationException) {
                continue;
            }
        }

        throw new RuntimeException('Could not generate a unique promo code.');
    }
}
