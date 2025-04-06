<?php

namespace App\Exports;

use App\Models\Participant;
use App\Models\Race;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ParticipantBriefingSignatureExport implements FromView, WithEvents
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $event->getSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 5);
                $event->getSheet()->getPageSetup()->setPrintArea("A1:D29");
            },
        ];
    }



    public function view(): View
    {
        return view('exports.signature', [
            'participants' => $this->query(),
            'race' => $this->race,
        ]);
    }
}
