<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Race;
use Barryvdh\DomPDF\Facade\Pdf;

class PrintRaceReceipts
{
    public function __construct(
        private Race $race
    ) {}

    public function query()
    {
        return $this->race
            ->participants()
            ->orderBy('bib');
    }

    public function stream(string $filename = 'receipts.pdf')
    {
        return Pdf::loadView('prints.receipt', [
            'race' => $this->race,
            'participants' => $this->query()->limit(2)->get(),
        ])
            ->setPaper('a4')
            ->addInfo([
                'Title' => $filename,
                'Author' => config('app.name'),
                'Creator' => config('app.name'),
                'PDFProducer' => config('app.name'),
            ])
            ->stream($filename);
    }
}
