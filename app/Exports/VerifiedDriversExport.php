<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Participant;
use App\Models\Race;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * The confirmed participants of a race with a transponder, for the national and international race organizers.
 */
class VerifiedDriversExport implements FromView, WithDrawings
{
    public function __construct(private Race $race) {}

    /**
     * @return EloquentCollection<int, Participant>
     */
    public function query(): EloquentCollection
    {
        return $this->race
            ->participants()
            ->has('transponders')
            ->orderBy('bib', 'ASC')
            ->get();
    }

    public function drawings(): BaseDrawing|array
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
        return view('exports.verified-drivers', [
            'participants' => $this->query()->groupBy('racingCategory.name')->sortKeys(),
            'race' => $this->race,
        ]);
    }
}
