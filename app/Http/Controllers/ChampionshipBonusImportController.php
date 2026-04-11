<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Bonus;
use App\Models\BonusType;
use App\Models\Championship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class ChampionshipBonusImportController extends Controller
{
    public function create(Championship $championship)
    {
        $this->authorize('create', Bonus::class);

        return view('bonus.import', [
            'championship' => $championship,
        ]);
    }

    public function store(Request $request, Championship $championship)
    {
        $this->authorize('create', Bonus::class);

        $validated = $this->validate($request, [
            'bonuses' => 'required|string',
        ]);

        $data = collect(Str::of($validated['bonuses'])->split('/[\n\r]+/'))->map(function ($line) {
            if (empty(mb_trim($line))) {
                return null;
            }

            $parsedLine = str_getcsv($line, ';');

            return [
                'driver' => $parsedLine[0] ?? null,
                'driver_licence' => ! empty($parsedLine[1]) ? $parsedLine[1] : null,
                'driver_fiscal_code' => ! empty($parsedLine[2]) ? $parsedLine[2] : null,
                'bonus_type' => $parsedLine[3] ?? null,
                'amount' => $parsedLine[4] ?? null,
            ];
        })->filter()->values();

        $validator = Validator::make(
            ['bonuses' => $data->toArray()],
            [
                'bonuses.*.driver' => 'required|string|max:250',
                'bonuses.*.driver_licence' => ['nullable', 'string', 'max:250'],
                'bonuses.*.driver_fiscal_code' => ['nullable', 'string', 'max:250'],
                'bonuses.*.bonus_type' => ['required', 'integer', new Enum(BonusType::class)],
                'bonuses.*.amount' => 'required|integer|min:1',
            ],
            [],
            [
                'bonuses.*.driver' => 'driver',
                'bonuses.*.driver_licence' => 'driver_licence',
                'bonuses.*.driver_fiscal_code' => 'driver_fiscal_code',
                'bonuses.*.bonus_type' => 'bonus_type',
                'bonuses.*.amount' => 'amount',
            ]
        );

        $validator->after(function ($validator) use ($data) {
            foreach ($data as $index => $row) {
                if (empty($row['driver_licence']) && empty($row['driver_fiscal_code'])) {
                    $validator->errors()->add(
                        "bonuses.{$index}.driver_licence",
                        __('Either driver licence or fiscal code is required.')
                    );
                }
            }
        });

        if ($validator->fails()) {
            $errors = $validator->errors();

            $messages = collect($errors->messages())->groupBy(function ($value, $key) {
                return Str::between($key, '.', '.');
            })->sortKeys()->mapWithKeys(function ($values, $line) {
                return [__('Line :line contains invalid data', ['line' => (int) $line + 1]) => $values->flatten()];
            });

            throw ValidationException::withMessages([
                'bonuses' => $messages,
            ]);
        }

        $toCreate = collect($validator->validated()['bonuses'])->map(function ($d) {
            $licenceHash = ! empty($d['driver_licence']) ? hash('sha512', $d['driver_licence']) : null;
            $fiscalCodeHash = ! empty($d['driver_fiscal_code']) ? hash('sha512', Str::lower($d['driver_fiscal_code'])) : null;

            return [
                'driver' => $d['driver'],
                'driver_licence' => $d['driver_licence'] ?? null,
                'driver_licence_hash' => $licenceHash,
                'driver_fiscal_code' => $d['driver_fiscal_code'] ?? null,
                'driver_fiscal_code_hash' => $fiscalCodeHash,
                'amount' => (int) $d['amount'],
                'bonus_type' => BonusType::from((int) $d['bonus_type']),
            ];
        });

        $duplicateErrors = [];

        $licenceHashes = $toCreate->pluck('driver_licence_hash')->filter()->values();
        $fiscalCodeHashes = $toCreate->pluck('driver_fiscal_code_hash')->filter()->values();

        if ($licenceHashes->isNotEmpty()) {
            $existingLicences = $championship->bonuses()
                ->whereIn('driver_licence_hash', $licenceHashes->toArray())
                ->pluck('driver_licence_hash');

            foreach ($toCreate as $index => $row) {
                if ($row['driver_licence_hash'] && $existingLicences->contains($row['driver_licence_hash'])) {
                    $duplicateErrors[__('Line :line contains invalid data', ['line' => $index + 1])][] =
                        __('A bonus with this driver licence already exists.');
                }
            }
        }

        if ($fiscalCodeHashes->isNotEmpty()) {
            $existingFiscalCodes = $championship->bonuses()
                ->whereIn('driver_fiscal_code_hash', $fiscalCodeHashes->toArray())
                ->pluck('driver_fiscal_code_hash');

            foreach ($toCreate as $index => $row) {
                if ($row['driver_fiscal_code_hash'] && $existingFiscalCodes->contains($row['driver_fiscal_code_hash'])) {
                    $duplicateErrors[__('Line :line contains invalid data', ['line' => $index + 1])][] =
                        __('A bonus with this fiscal code already exists.');
                }
            }
        }

        if (! empty($duplicateErrors)) {
            throw ValidationException::withMessages([
                'bonuses' => $duplicateErrors,
            ]);
        }

        $championship->bonuses()->createMany($toCreate->toArray());

        return to_route('championships.bonuses.index', $championship)
            ->with('flash.banner', __(':count bonuses imported.', ['count' => $toCreate->count()]));
    }
}
