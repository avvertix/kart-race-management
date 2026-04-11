<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Bonus;
use App\Models\BonusType;
use App\Models\Championship;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CreateBonus
{
    /**
     * @param  array{driver: string, driver_licence: ?string, driver_fiscal_code: ?string, bonus_type: int, amount: int}  $input
     */
    public function __invoke(Championship $championship, array $input): Bonus
    {
        Validator::make($input, [
            'driver' => 'required|string|max:250',
            'driver_licence' => [
                'required_without:driver_fiscal_code',
                'nullable',
                'string',
                'max:250',
            ],
            'driver_fiscal_code' => [
                'required_without:driver_licence',
                'nullable',
                'string',
                'max:250',
            ],
            'bonus_type' => ['required', 'integer', new Enum(BonusType::class)],
            'amount' => 'required|integer|min:1',
        ])->validate();

        $licenceHash = isset($input['driver_licence']) ? hash('sha512', $input['driver_licence']) : null;
        $fiscalCodeHash = isset($input['driver_fiscal_code']) ? hash('sha512', Str::lower($input['driver_fiscal_code'])) : null;

        $uniqueRules = [];

        if ($licenceHash) {
            $uniqueRules['driver_licence'] = [
                'required',
                'string',
                Rule::unique('bonuses', 'driver_licence_hash')
                    ->where(fn ($query) => $query->where('championship_id', $championship->getKey())),
            ];
        }

        if ($fiscalCodeHash) {
            $uniqueRules['driver_fiscal_code'] = [
                'required',
                'string',
                Rule::unique('bonuses', 'driver_fiscal_code_hash')
                    ->where(fn ($query) => $query->where('championship_id', $championship->getKey())),
            ];
        }

        if (! empty($uniqueRules)) {
            Validator::validate(array_filter([
                'driver_licence' => $licenceHash,
                'driver_fiscal_code' => $fiscalCodeHash,
            ]), $uniqueRules);
        }

        return $championship->bonuses()->create([
            'driver' => $input['driver'],
            'driver_licence' => $input['driver_licence'] ?? null,
            'driver_licence_hash' => $licenceHash,
            'driver_fiscal_code' => $input['driver_fiscal_code'] ?? null,
            'driver_fiscal_code_hash' => $fiscalCodeHash,
            'amount' => $input['amount'],
            'bonus_type' => BonusType::from((int) ($input['bonus_type'])),
        ]);
    }
}
