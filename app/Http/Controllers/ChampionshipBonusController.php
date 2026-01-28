<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Bonus;
use App\Models\BonusType;
use App\Models\Championship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ChampionshipBonusController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Bonus::class, 'bonus');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Championship $championship)
    {
        return view('bonus.index', [
            'championship' => $championship,
            'bonuses' => $championship->bonuses()
                ->withCount('usages')
                ->withSum(['usages as used_amount' => function ($query) {
                    $query->select(DB::raw('sum(amount)'));
                }], 'used_amount')
                ->orderBy('driver', 'ASC')
                ->get(),
            'fixed_bonus_amount' => $championship->bonuses->fixed_bonus_amount ?? config('races.bonus_amount'),
            'bonus_mode' => $championship->bonuses->bonus_mode ?? \App\Models\BonusMode::CREDIT,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Championship $championship)
    {
        return view('bonus.create', [
            'championship' => $championship,
            'fixed_bonus_amount' => $championship->bonuses->fixed_bonus_amount ?? config('races.bonus_amount'),
            'bonus_mode' => $championship->bonuses->bonus_mode ?? \App\Models\BonusMode::CREDIT,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Championship $championship)
    {
        $validated = $this->validate($request, [
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
        ]);

        $licenceHash = isset($validated['driver_licence']) ? hash('sha512', $validated['driver_licence']) : null;
        $fiscalCodeHash = isset($validated['driver_fiscal_code']) ? hash('sha512', Str::lower($validated['driver_fiscal_code'])) : null;

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

        $bonus = $championship->bonuses()->create([
            'driver' => $validated['driver'],
            'driver_licence' => $validated['driver_licence'] ?? null,
            'driver_licence_hash' => $licenceHash,
            'driver_fiscal_code' => $validated['driver_fiscal_code'] ?? null,
            'driver_fiscal_code_hash' => $fiscalCodeHash,
            'amount' => $validated['amount'],
            'bonus_type' => BonusType::from((int) ($validated['bonus_type'])),
        ]);

        return redirect()->route('championships.bonuses.index', $championship)
            ->with('flash.banner', __('Bonus activated for :driver.', [
                'driver' => $bonus->driver,
            ]));
    }

    /**
     * Display the specified resource.
     */
    public function show(Bonus $bonus)
    {
        $bonus->load([
            'championship',
            'usages.race',
        ]);

        $bonus->loadCount('usages');
        $bonus->loadSum(['usages as used_amount' => function ($query) {
            $query->select(DB::raw('sum(amount)'));
        }], 'used_amount');

        return view('bonus.show', [
            'bonus' => $bonus,
            'championship' => $bonus->championship,
            'bonusUsage' => $bonus->usages,
            'fixed_bonus_amount' => $bonus->championship->bonuses->fixed_bonus_amount ?? config('races.bonus_amount'),
            'bonus_mode' => $bonus->championship->bonuses->bonus_mode ?? \App\Models\BonusMode::CREDIT,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bonus $bonus)
    {
        $bonus->load('championship');

        return view('bonus.edit', [
            'bonus' => $bonus,
            'championship' => $bonus->championship,
            'fixed_bonus_amount' => $bonus->championship->bonuses->fixed_bonus_amount ?? config('races.bonus_amount'),
            'bonus_mode' => $bonus->championship->bonuses->bonus_mode ?? \App\Models\BonusMode::CREDIT,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bonus $bonus)
    {
        $championship = $bonus->championship;

        $validated = $this->validate($request, [
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
        ]);

        $licenceHash = isset($validated['driver_licence']) ? hash('sha512', $validated['driver_licence']) : null;
        $fiscalCodeHash = isset($validated['driver_fiscal_code']) ? hash('sha512', $validated['driver_fiscal_code']) : null;

        $uniqueRules = [];

        if ($licenceHash) {
            $uniqueRules['driver_licence'] = [
                'required',
                'string',
                Rule::unique('bonuses', 'driver_licence_hash')
                    ->ignoreModel($bonus)
                    ->where(fn ($query) => $query->where('championship_id', $championship->getKey())),
            ];
        }

        if ($fiscalCodeHash) {
            $uniqueRules['driver_fiscal_code'] = [
                'required',
                'string',
                Rule::unique('bonuses', 'driver_fiscal_code_hash')
                    ->ignoreModel($bonus)
                    ->where(fn ($query) => $query->where('championship_id', $championship->getKey())),
            ];
        }

        if (! empty($uniqueRules)) {
            Validator::validate(array_filter([
                'driver_licence' => $licenceHash,
                'driver_fiscal_code' => $fiscalCodeHash,
            ]), $uniqueRules);
        }

        $bonus->update([
            'driver' => $validated['driver'],
            'driver_licence' => $validated['driver_licence'] ?? null,
            'driver_licence_hash' => $licenceHash,
            'driver_fiscal_code' => $validated['driver_fiscal_code'] ?? null,
            'driver_fiscal_code_hash' => $fiscalCodeHash,
            'amount' => $validated['amount'],
            'bonus_type' => BonusType::from((int) ($validated['bonus_type'])),
        ]);

        return redirect()->route('championships.bonuses.index', $championship)
            ->with('flash.banner', __('Bonus for :driver updated.', [
                'driver' => $bonus->driver,
            ]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bonus $bonus)
    {
        //
    }
}
