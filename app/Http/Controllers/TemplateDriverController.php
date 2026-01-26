<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemplateDriverRequest;
use App\Http\Requests\UpdateTemplateDriverRequest;
use App\Models\DriverLicence;
use App\Models\Sex;
use App\Models\TemplateDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class TemplateDriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $templates = $request->user()
            ->templateDrivers()
            ->orderBy('name')
            ->get();

        return view('template-participant.index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('template-participant.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTemplateDriverRequest $request)
    {
        $validated = $request->validated();

        $template = $request->user()->templateDrivers()->create([
            'name' => $validated['name'],
            'bib' => $validated['bib'],
            'driver' => $this->buildDriverData($validated),
            'competitor' => $this->buildCompetitorData($validated),
            'mechanic' => $this->buildMechanicData($validated),
            'vehicles' => $this->buildVehicleData($validated),
        ]);

        return redirect()->route('drivers.index')
            ->with('flash.banner', __(':name created.', ['name' => $template->name]));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, TemplateDriver $driver)
    {
        $this->authorize('update', $driver);

        return view('template-participant.edit', [
            'template' => $driver,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTemplateDriverRequest $request, TemplateDriver $driver)
    {
        $this->authorize('update', $driver);

        $validated = $request->validated();

        $driver->update([
            'name' => $validated['name'],
            'bib' => $validated['bib'],
            'driver' => $this->buildDriverData($validated),
            'competitor' => $this->buildCompetitorData($validated),
            'mechanic' => $this->buildMechanicData($validated),
            'vehicles' => $this->buildVehicleData($validated),
        ]);

        return redirect()->route('drivers.index')
            ->with('flash.banner', __(':name updated.', ['name' => $driver->name]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, TemplateDriver $driver)
    {
        $this->authorize('delete', $driver);

        $name = $driver->name;

        $driver->delete();

        return redirect()->route('drivers.index')
            ->with('flash.banner', __(':name deleted.', ['name' => $name]));
    }

    /**
     * Build driver data array from validated input.
     */
    protected function buildDriverData(array $validated): array
    {
        return [
            'first_name' => $validated['driver_first_name'],
            'last_name' => $validated['driver_last_name'],
            'licence_type' => $validated['driver_licence_type'] ?? DriverLicence::LOCAL_NATIONAL->value,
            'licence_number' => $validated['driver_licence_number'] ?? null,
            'licence_renewed_at' => Date::normalizeToDateString($validated['driver_licence_renewed_at'] ?? null),
            'fiscal_code' => $validated['driver_fiscal_code'] ?? null,
            'nationality' => $validated['driver_nationality'] ?? null,
            'email' => $validated['driver_email'] ?? null,
            'phone' => $validated['driver_phone'] ?? null,
            'birth_date' => Date::normalizeToDateString($validated['driver_birth_date'] ?? null),
            'birth_place' => $validated['driver_birth_place'] ?? null,
            'medical_certificate_expiration_date' => Date::normalizeToDateString($validated['driver_medical_certificate_expiration_date'] ?? null),
            'residence_address' => $this->buildAddressData($validated, 'driver_residence'),
            'sex' => $validated['driver_sex'] ?? Sex::UNSPECIFIED,
        ];
    }

    /**
     * Build competitor data array from validated input.
     */
    protected function buildCompetitorData(array $validated): ?array
    {
        if (empty($validated['competitor_first_name']) && empty($validated['competitor_last_name'])) {
            return null;
        }

        return [
            'first_name' => $validated['competitor_first_name'] ?? null,
            'last_name' => $validated['competitor_last_name'] ?? null,
            'licence_type' => $validated['competitor_licence_type'] ?? null,
            'licence_number' => $validated['competitor_licence_number'] ?? null,
            'licence_renewed_at' => Date::normalizeToDateString($validated['competitor_licence_renewed_at'] ?? null),
            'fiscal_code' => $validated['competitor_fiscal_code'] ?? null,
            'nationality' => $validated['competitor_nationality'] ?? null,
            'email' => $validated['competitor_email'] ?? null,
            'phone' => $validated['competitor_phone'] ?? null,
            'birth_date' => Date::normalizeToDateString($validated['competitor_birth_date'] ?? null),
            'birth_place' => $validated['competitor_birth_place'] ?? null,
            'residence_address' => $this->buildAddressData($validated, 'competitor_residence'),
        ];
    }

    /**
     * Build mechanic data array from validated input.
     */
    protected function buildMechanicData(array $validated): ?array
    {
        if (empty($validated['mechanic_name']) && empty($validated['mechanic_licence_number'])) {
            return null;
        }

        return [
            'name' => $validated['mechanic_name'] ?? null,
            'licence_number' => $validated['mechanic_licence_number'] ?? null,
        ];
    }

    /**
     * Build vehicle data array from validated input.
     */
    protected function buildVehicleData(array $validated): ?array
    {
        if (empty($validated['vehicle_chassis_manufacturer']) && empty($validated['vehicle_engine_manufacturer'])) {
            return null;
        }

        return [[
            'chassis_manufacturer' => $validated['vehicle_chassis_manufacturer'] ?? null,
            'engine_manufacturer' => mb_strtolower($validated['vehicle_engine_manufacturer'] ?? ''),
            'engine_model' => mb_strtolower($validated['vehicle_engine_model'] ?? ''),
            'oil_manufacturer' => $validated['vehicle_oil_manufacturer'] ?? null,
            'oil_type' => $validated['vehicle_oil_type'] ?? null,
            'oil_percentage' => $validated['vehicle_oil_percentage'] ?? null,
        ]];
    }

    /**
     * Build address data array from validated input.
     */
    protected function buildAddressData(array $validated, string $prefix): ?array
    {
        if (empty($validated[$prefix.'_address']) &&
            empty($validated[$prefix.'_city']) &&
            empty($validated[$prefix.'_postal_code'])) {
            return null;
        }

        return [
            'address' => $validated[$prefix.'_address'] ?? null,
            'city' => $validated[$prefix.'_city'] ?? null,
            'province' => $validated[$prefix.'_province'] ?? null,
            'postal_code' => $validated[$prefix.'_postal_code'] ?? null,
        ];
    }
}
