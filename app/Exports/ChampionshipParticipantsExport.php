<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Championship;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ChampionshipParticipantsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        private Championship $championship,
    ) {}

    public function headings(): array
    {
        return [
            __('Number'),
            __('Name'),
            __('Email'),
        ];
    }

    public function query(): Builder
    {
        $subQuery = Participant::where('championship_id', $this->championship->getKey())
            ->groupBy('driver_licence')
            ->select(DB::raw('MAX(id) as identifier'));

        return Participant::query()
            ->whereIn('id', $subQuery)
            ->orderBy('bib', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * @param  Participant  $invoice
     */
    public function map($participant): array
    {
        return [
            $participant->bib,
            $participant->fullName,
            $participant->email,
        ];
    }
}
