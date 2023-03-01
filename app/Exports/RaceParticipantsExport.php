<?php

namespace App\Exports;

use App\Models\CompetitorLicence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\Sex;
use App\User;
use Vitorccs\LaravelCsv\Concerns\Exportable;
use Vitorccs\LaravelCsv\Concerns\FromQuery;
use Vitorccs\LaravelCsv\Concerns\WithHeadings;
use Vitorccs\LaravelCsv\Concerns\WithMapping;

class RaceParticipantsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        private Race $race
        )
    {
        
    }
    
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
            __('Engine Manufacturer'),
            __('Engine Model'),
            __('Oil Manufacturer'),
            __('Oil Type'),
            __('Oil Percentage'),
        ];
    }

    /**
     * @param \App\Models\Participant $participant
     */
    public function map($participant): array
    {
        $vehicle = $participant->vehicles[0];

        return [
            $participant->bib,
            $participant->category()->name,

            $participant->first_name,
            $participant->last_name,
            $participant->licence_type?->localizedName(),
            $participant->driver['licence_number'],
            $participant->driver['nationality'],
            $participant->driver['birth_date'],
            $participant->driver['birth_place'],
            $participant->driver['medical_certificate_expiration_date'],
            __(':address, :city :province :postal_code', [
                'address' => $participant->driver['residence_address']['address'] ?? null,
                'city' => $participant->driver['residence_address']['city'] ?? null,
                'postal_code' => $participant->driver['residence_address']['postal_code'] ?? null,
                'province' => $participant->driver['residence_address']['province'] ?? null,
            ]),
            Sex::from($participant->driver['sex'])->localizedName(),

            $participant->competitor['first_name'] ?? null,
            $participant->competitor['last_name'] ?? null,
            ($participant->competitor['licence_type'] ?? false) ? CompetitorLicence::from($participant->competitor['licence_type'])->name : null,
            $participant->competitor['licence_number'] ?? null,
            $participant->competitor['nationality'] ?? null,
            $participant->competitor['birth_date'] ?? null,
            $participant->competitor['birth_place'] ?? null,
            __(':address, :city :province :postal_code', [
                'address' => $participantcompetitordriver['residence_address']['address'] ?? null,
                'city' => $participant->competitor['residence_address']['city'] ?? null,
                'postal_code' => $participant->competitor['residence_address']['postal_code'] ?? null,
                'province' => $participant->competitor['residence_address']['province'] ?? null,
            ]),

            $participant->mechanic['name'] ?? null,
            $participant->mechanic['licence_number'] ?? null,

            $vehicle['chassis_manufacturer']  ?? null,
            $vehicle['engine_manufacturer']  ?? null,
            $vehicle['engine_model']  ?? null,
            $vehicle['oil_manufacturer']  ?? null,
            $vehicle['oil_type']  ?? null,
            $vehicle['oil_percentage']  ?? null,
        ];
    }
}

