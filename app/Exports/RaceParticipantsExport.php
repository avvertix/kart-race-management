<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\CompetitorLicence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\Sex;
use Vitorccs\LaravelCsv\Concerns\Exportables\Exportable;
use Vitorccs\LaravelCsv\Concerns\Exportables\FromQuery;
use Vitorccs\LaravelCsv\Concerns\WithHeadings;
use Vitorccs\LaravelCsv\Concerns\WithMapping;

class RaceParticipantsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        private Race $race
    ) {}

    public function query()
    {
        return $this->race
            ->participants()
            ->orderBy('bib');
    }

    public function headings(): array
    {
        return [
            __('Number'),
            __('Category'),
            __('Status'),

            __('Driver Name'),
            __('Driver Surname'),
            __('Driver Licence Type'),
            __('Driver Licence Number'),
            __('Driver Nationality'),
            __('Driver Birth date'),
            __('Driver Birth place'),
            __('Driver medical certificate expiration'),
            __('Driver Residence address'),
            __('Driver Sex'),

            __('Competitor Name'),
            __('Competitor Surname'),
            __('Competitor Licence Type'),
            __('Competitor Licence Number'),
            __('Competitor Nationality'),
            __('Competitor Birth date'),
            __('Competitor Birth place'),
            __('Competitor Residence address'),

            __('Mechanic Name'),
            __('Mechanic Licence Number'),

            __('Chassis Manufacturer'),
            __('Chassis Model'),
            __('Chassis homologation number'),
            __('Chassis serial number'),
            __('Engine Manufacturer'),
            __('Engine Model'),
            __('Engine homologation number'),
            __('Engine serial number'),
            __('Oil Manufacturer'),
            __('Oil Type'),
            __('Oil Percentage'),

            __('Payment'),
        ];
    }

    /**
     * @param  Participant  $participant
     */
    public function map($participant): array
    {
        $vehicle = $participant->vehicles[0] ?? [];

        return [
            $participant->bib,
            $participant->racingCategory->name,
            $participant->registration_completed_at ? __('completed') : null,

            $participant->first_name,
            $participant->last_name,
            $participant->licence_type?->localizedName(),
            $participant->driver['licence_number'],
            $participant->driver['nationality'],
            $participant->driver['birth_date'],
            $participant->driver['birth_place'],
            $participant->driver['medical_certificate_expiration_date'],
            __(':address :city :province :postal_code', [
                'address' => $participant->driver['residence_address']['address'] ?? null,
                'city' => $participant->driver['residence_address']['city'] ?? null,
                'postal_code' => $participant->driver['residence_address']['postal_code'] ?? null,
                'province' => $participant->driver['residence_address']['province'] ?? null,
            ]),
            filled($participant->driver['sex']) ? Sex::from((int) ($participant->driver['sex']))->localizedName() : Sex::UNSPECIFIED->localizedName(),

            $participant->competitor['first_name'] ?? null,
            $participant->competitor['last_name'] ?? null,
            ($participant->competitor['licence_type'] ?? false) ? CompetitorLicence::from((int) ($participant->competitor['licence_type']))->name : null,
            $participant->competitor['licence_number'] ?? null,
            $participant->competitor['nationality'] ?? null,
            $participant->competitor['birth_date'] ?? null,
            $participant->competitor['birth_place'] ?? null,
            mb_trim(__(':address :city :province :postal_code', [
                'address' => $participant->competitor['residence_address']['address'] ?? null,
                'city' => $participant->competitor['residence_address']['city'] ?? null,
                'postal_code' => $participant->competitor['residence_address']['postal_code'] ?? null,
                'province' => $participant->competitor['residence_address']['province'] ?? null,
            ])),

            $participant->mechanic['name'] ?? null,
            $participant->mechanic['licence_number'] ?? null,

            $vehicle['chassis_manufacturer'] ?? null,
            $vehicle['chassis_model'] ?? null,
            $vehicle['chassis_homologation'] ?? null,
            $vehicle['chassis_number'] ?? null,
            $vehicle['engine_manufacturer'] ?? null,
            $vehicle['engine_model'] ?? null,
            $vehicle['engine_homologation'] ?? null,
            $vehicle['engine_number'] ?? null,
            $vehicle['oil_manufacturer'] ?? null,
            $vehicle['oil_type'] ?? null,
            $vehicle['oil_percentage'] ?? null,

            $participant->payment_channel?->localizedName(),
        ];
    }
}
