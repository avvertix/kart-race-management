<?php

namespace App\Exports;

use App\Models\Participant;
use App\Models\Race;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class AciParticipantPromotionExport implements FromView, WithDrawings
{

    public function __construct(private Race $race)
    {
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function query(): EloquentCollection
    {
        return $this->race
            ->participants()
            ->has('transponders')
            ->orderBy('bib', 'ASC')
            ->get();
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Karting - Coppa Italia di Zona');
        $drawing->setPath(resource_path('/images/aci-coppa-italia-banner.png'));
        $drawing->setHeight(164);
        $drawing->setCoordinates('A1');

        return $drawing;
    }


    public function view(): View
    {
        return view('exports.aci', [
            'participants' => $this->query()->groupBy('racingCategory.short_name')->sortKeys(),
            'race' => $this->race,
        ]);
    }
}
