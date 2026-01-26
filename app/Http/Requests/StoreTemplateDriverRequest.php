<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\CompetitorLicence;
use App\Models\DriverLicence;
use App\Models\Sex;
use App\Rules\DateFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTemplateDriverRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:250'],
            'bib' => ['required', 'integer', 'min:1'],

            // Driver fields
            'driver_first_name' => ['required', 'string', 'max:250'],
            'driver_last_name' => ['required', 'string', 'max:250'],
            'driver_licence_type' => ['nullable', new Enum(DriverLicence::class)],
            'driver_licence_number' => ['nullable', 'string', 'max:250'],
            'driver_licence_renewed_at' => ['nullable', 'string'],
            'driver_fiscal_code' => ['nullable', 'string', 'max:250'],
            'driver_nationality' => ['nullable', 'string', 'max:250'],
            'driver_email' => ['nullable', 'string', 'email', 'max:250'],
            'driver_phone' => ['nullable', 'string', 'max:250'],
            'driver_birth_date' => ['nullable', 'string', new DateFormat],
            'driver_birth_place' => ['nullable', 'string', 'max:250'],
            'driver_medical_certificate_expiration_date' => ['nullable', 'string', new DateFormat],
            'driver_residence_address' => ['nullable', 'string', 'max:250'],
            'driver_residence_city' => ['nullable', 'string', 'max:250'],
            'driver_residence_province' => ['nullable', 'string', 'max:250'],
            'driver_residence_postal_code' => ['nullable', 'string', 'max:250'],
            'driver_sex' => ['nullable', new Enum(Sex::class)],

            // Competitor fields
            'competitor_first_name' => ['nullable', 'string', 'max:250'],
            'competitor_last_name' => ['nullable', 'string', 'max:250'],
            'competitor_licence_type' => ['nullable', new Enum(CompetitorLicence::class)],
            'competitor_licence_number' => ['nullable', 'string', 'max:250'],
            'competitor_licence_renewed_at' => ['nullable', 'string'],
            'competitor_fiscal_code' => ['nullable', 'string', 'max:250'],
            'competitor_nationality' => ['nullable', 'string', 'max:250'],
            'competitor_email' => ['nullable', 'string', 'email', 'max:250'],
            'competitor_phone' => ['nullable', 'string', 'max:250'],
            'competitor_birth_date' => ['nullable', 'string', new DateFormat],
            'competitor_birth_place' => ['nullable', 'string', 'max:250'],
            'competitor_residence_address' => ['nullable', 'string', 'max:250'],
            'competitor_residence_city' => ['nullable', 'string', 'max:250'],
            'competitor_residence_province' => ['nullable', 'string', 'max:250'],
            'competitor_residence_postal_code' => ['nullable', 'string', 'max:250'],

            // Mechanic fields
            'mechanic_name' => ['nullable', 'string', 'max:250'],
            'mechanic_licence_number' => ['nullable', 'string', 'max:250'],

            // Vehicle fields
            'vehicle_chassis_manufacturer' => ['nullable', 'string', 'max:250'],
            'vehicle_engine_manufacturer' => ['nullable', 'string', 'max:250'],
            'vehicle_engine_model' => ['nullable', 'string', 'max:250'],
            'vehicle_oil_manufacturer' => ['nullable', 'string', 'max:250'],
            'vehicle_oil_type' => ['nullable', 'string', 'max:250'],
            'vehicle_oil_percentage' => ['nullable', 'string', 'max:250'],
        ];
    }
}
