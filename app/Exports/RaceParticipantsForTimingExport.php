<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Participant;
use App\Models\Race;
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
    ) {
        if (is_null($this->mappings)) {
            $this->mappings = $this->loadTransponderMappingsFromFile();
        }
    }

    public function query()
    {
        return $this->race
            ->participants()
            ->has('transponders')
            ->orderBy('category_id', 'ASC')
            ->orderBy('bib', 'ASC');
    }

    public function headings(): array
    {
        return [
            'No',
            'Class',
            'LastName',
            'FirstName',
            'CarRegistration',
            'DriverRegistration',
            'Transponder1',
            'Transponder2',
            'Additional1',
            'Additional2',
            'Additional3',
            'Additional4',
            'Additional5',
            'Additional6',
            'Additional7',
            'Additional8',
        ];
    }

    /**
     * @param  Participant  $participant
     */
    public function map($participant): array
    {
        $vehicle = $participant->vehicles[0] ?? [];

        $transponders = $participant->transponders->map(function ($t) {
            return $this->mappings->get($t->code, $t->code);
        });

        // For both car and driver registration we use the first 8 characters of the licence hash
        // car registration => identifier assigned to bib+class (max 8 chars)
        // driver registration => identifier assigned to the driver data (max 8 chars)
        $registration_identifier = mb_substr($participant->driver_licence, 0, 8);

        $engine_mapping = config('engine.normalization');

        $category = $participant->racingCategory->getTimekeepLabel();

        $swap = [
            'à' => 'a\'',
            'è' => 'e\'',
            'é' => 'e\'',
            'ì' => 'i\'',
            'ò' => 'o\'',
            'ó' => 'o\'',
            'ù' => 'u\'',
            '’' => '\'',
        ];

        return [
            $participant->bib,
            $category,
            str($participant->first_name)->lower()->swap($swap)->ascii()->upper()->toString(),
            str($participant->last_name)->lower()->swap($swap)->ascii()->upper()->toString(),
            $registration_identifier,
            $registration_identifier,
            $transponders->first(),
            $transponders->skip(1)->last(),
            '',
            $participant->wildcard ? 'WILDCARD' : '',
            ($this->race->isZonal() && isset($participant->properties['out_of_zone']) && $participant->properties['out_of_zone']) ? __('Out of zone') : '',
            $this->race->event_start_at->toDateString(),
            $participant->licence_type->localizedName(),
            ! empty($vehicle) ? $engine_mapping[mb_strtolower($vehicle['engine_manufacturer'])] ?? mb_strtoupper($vehicle['engine_manufacturer']) : '',
            ! empty($vehicle) ? mb_strtoupper($vehicle['engine_model']) : '',
            $participant->driver['phone'].' - '.($participant->competitor['phone'] ?? ''),
        ];
    }

    protected function loadTransponderMappingsFromFile(): Collection
    {
        $path = resource_path('transponders/code-mapping.txt');

        if (! file_exists($path)) {
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
}
