<?php

namespace App\Exports;

use App\Models\CompetitorLicence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\Sex;
use App\User;
use Illuminate\Support\Collection;
use Vitorccs\LaravelCsv\Concerns\Exportable;
use Vitorccs\LaravelCsv\Concerns\FromQuery;
use Vitorccs\LaravelCsv\Concerns\WithHeadings;
use Vitorccs\LaravelCsv\Concerns\WithMapping;

class RaceParticipantsForTimingExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        private Race $race,
        private ?Collection $mappings = null
        )
    {
        if(is_null($this->mappings)){
            $this->mappings = $this->loadTransponderMappingsFromFile();
        }
    }

    protected function loadTransponderMappingsFromFile() : Collection
    {
        $path = resource_path('transponders/code-mapping.txt');

        if(!file_exists($path)){
            return collect();
        }

        $raw = file_get_contents($path);

        $mappingLines = preg_split("/\r?\n|\r/", $raw);

        return collect($mappingLines)
                ->filter()
                ->mapWithKeys(function ($l) {
                    $splitted = str($l)->split('/\t|\s/')->filter();
                    return [''.$splitted[0] => $splitted[1]];
                });

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
            "No",
            "Class",
            "FirstName",
            "LastName",
            "CarRegistration",
            "DriverRegistration",
            "Transponder1",
            "Transponder2",
            "Additional1",
            "Additional2",
            "Additional3",
            "Additional4",
            "Additional5",
            "Additional6",
            "Additional7",
            "Additional8",
        ];
    }

    /**
     * @param \App\Models\Participant $participant
     */
    public function map($participant): array
    {
        $vehicle = $participant->vehicles[0];

        $transponders = $participant->transponders->map(function($t){
            return $this->mappings->get($t->code, $t->code);
        });

        // For both car and driver registration we use the first 8 characters of the licence hash
        // car registration => identifier assigned to bib+class (max 8 chars)
        // driver registration => identifier assigned to the driver data (max 8 chars)
        $registration_identifier = substr($participant->driver_licence, 0, 8);

        return [
            $participant->bib,
            $participant->category()->name,
            strtoupper($participant->first_name),
            strtoupper($participant->last_name),
            $registration_identifier, 
            $registration_identifier, 
            $transponders->first(),
            $transponders->skip(1)->last(),
            "",
            "",
            "",
            "",
            $participant->licence_type->localizedName(),
            $vehicle['engine_manufacturer'],
            $vehicle['engine_model'],
            $participant->driver['phone'] . ' - ' . ($participant->competitor['phone'] ?? ''),
            $participant->driver['licence_number'],
        ];
    }
}

