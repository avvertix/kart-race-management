<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Category;
use App\Models\Race;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PrintRacePenaltySheet
{
    public function __construct(
        private Race $race,
        private array $groups = []
    ) {}

    public function stream(string $filename = 'penalty-sheet.pdf')
    {
        return Pdf::loadView('prints.penalty-sheet', [
            'race' => $this->race,
            'championship' => $this->race->championship,
            'groups' => $this->buildGroups(),
            'bannerBase64' => $this->bannerBase64(),
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

    private function buildGroups(): Collection
    {
        $this->race->load('championship.categories');
        $championship = $this->race->championship;

        if (empty($this->groups)) {
            $participants = $this->race->participants()
                ->confirmed()
                ->with('racingCategory')
                ->orderBy('bib')
                ->get()
                ->groupBy('category_id');

            return $championship->categories
                ->filter(fn (Category $category) => $participants->has($category->id))
                ->map(fn (Category $category) => $this->buildGroup(
                    $category->name,
                    $participants->get($category->id),
                    false
                ))
                ->values();
        }

        return collect($this->groups)->map(function (array $categoryUlids) use ($championship) {
            $categories = $championship->categories->whereIn('ulid', $categoryUlids);
            $categoryIds = $categories->pluck('id');
            $title = $categories->pluck('name')->join(' / ');

            $participants = $this->race->participants()
                ->confirmed()
                ->with('racingCategory')
                ->whereIn('category_id', $categoryIds)
                ->orderBy('bib')
                ->get();

            return $this->buildGroup($title, $participants, $categories->count() > 1);
        })->filter(fn (array $group) => $group['participants']->isNotEmpty())->values();
    }

    /**
     * @param  Collection<int, \App\Models\Participant>  $participants
     * @return array{title: string, participants: Collection, showCategory: bool, minRows: int}
     */
    private function buildGroup(string $title, Collection $participants, bool $showCategory): array
    {
        return [
            'title' => $title,
            'participants' => $participants,
            'showCategory' => $showCategory,
            'minRows' => max($participants->count(), 36),
        ];
    }

    private function bannerBase64(): ?string
    {
        $championship = $this->race->championship;

        if (! $championship->banner_path) {
            return null;
        }

        $path = Storage::disk('championship-banners')->path($championship->banner_path);

        if (! file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        $mime = mime_content_type($path);

        return 'data:'.$mime.';base64,'.base64_encode($content);
    }
}
