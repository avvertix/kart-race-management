<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Sex;
use App\Rules\DateFormat;
use App\Rules\FiscalCodeFormatRule;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Data;

/**
 * The personal details of a licence holder, mirroring the Participant driver and competitor fields.
 * Companies (VAT number, no fiscal code or birth date) can extend it later.
 */
class HolderData extends Data
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public Sex $sex,
        public string $birth_date,
        public string $birth_place,
        public ?string $fiscal_code,
        public string $nationality,
        public string $email,
        public string $phone,
        public AddressData $residence_address,
    ) {}

    /**
     * The validation rules of the holder fields under the given input key.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function validationRules(array $input, string $prefix = 'holder'): array
    {
        return [
            "{$prefix}.first_name" => ['required', 'string', 'max:250'],
            "{$prefix}.last_name" => ['required', 'string', 'max:250'],
            "{$prefix}.sex" => ['required', new Enum(Sex::class)],
            "{$prefix}.birth_date" => ['required', 'string', new DateFormat],
            "{$prefix}.birth_place" => ['required', 'string', 'max:250'],
            "{$prefix}.fiscal_code" => [
                'nullable',
                'string',
                'max:250',
                Rule::when(filled(data_get($input, "{$prefix}.fiscal_code")), [new FiscalCodeFormatRule]),
            ],
            "{$prefix}.nationality" => ['required', 'string', 'max:250'],
            "{$prefix}.email" => ['required', 'string', 'email', 'max:250'],
            "{$prefix}.phone" => ['required', 'string', 'max:250'],
            "{$prefix}.residence_address.address" => ['required', 'string', 'max:250'],
            "{$prefix}.residence_address.city" => ['required', 'string', 'max:250'],
            "{$prefix}.residence_address.province" => ['nullable', 'string', 'max:250'],
            "{$prefix}.residence_address.postal_code" => ['required', 'string', 'max:250'],
        ];
    }

    /**
     * Build the holder from validated input, normalizing the birth date, the fiscal code and the email.
     * The birth date passed DateFormat, so it is Y-m-d or d/m/Y; with dashes instead of slashes PHP reads the latter as day-month-year.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            first_name: $validated['first_name'],
            last_name: $validated['last_name'],
            sex: Sex::from((int) $validated['sex']),
            birth_date: CarbonImmutable::parse(str_replace('/', '-', $validated['birth_date']))->toDateString(),
            birth_place: $validated['birth_place'],
            fiscal_code: filled($validated['fiscal_code'] ?? null) ? mb_strtoupper($validated['fiscal_code']) : null,
            nationality: $validated['nationality'],
            email: mb_strtolower($validated['email']),
            phone: $validated['phone'],
            residence_address: new AddressData(
                address: $validated['residence_address']['address'],
                city: $validated['residence_address']['city'],
                province: $validated['residence_address']['province'] ?? null,
                postal_code: $validated['residence_address']['postal_code'],
            ),
        );
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * What tells two holders apart: the fiscal code, or the name and birth date without one.
     */
    public function identityKey(): string
    {
        return mb_strtolower(filled($this->fiscal_code) ? $this->fiscal_code : "{$this->fullName()} {$this->birth_date}");
    }
}
