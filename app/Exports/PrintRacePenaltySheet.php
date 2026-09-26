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
    public const WILDCARD_SUFFIX = ':wildcard';

    public function __construct(
        private Race $race,
        private array $groups = [],
        private bool $separateWildcards = false,
    ) {}

    public function stream(string $filename = 'penalty-sheet.pdf')
    {
        return Pdf::loadView('prints.penalty-sheet', $this->viewData())
            ->setPaper('a4')
            ->addInfo([
                'Title' => $filename,
                'Author' => config('app.name'),
                'Creator' => config('app.name'),
                'PDFProducer' => config('app.name'),
            ])
            ->stream($filename);
    }

    /**
     * @return array{race: Race, championship: \App\Models\Championship, groups: Collection, bannerBase64: ?string, markWildcards: bool}
     */
    private function viewData(): array
    {
        return [
            'race' => $this->race,
            'championship' => $this->race->championship,
            'groups' => $this->buildGroups(),
            'bannerBase64' => $this->bannerBase64(),
            'markWildcards' => $this->race->championship->wildcard?->enabled ?? false,
        ];
    }

    /**
     * Build the printable groups.
     *
     * Each requested group is a list of keys: a category ulid, or, when wildcards
     * are handled as separate categories, a category ulid suffixed with ":wildcard".
     */
    private function buildGroups(): Collection
    {
        $this->race->load('championship.categories');

        $separateWildcards = $this->shouldSeparateWildcards();

        $participants = $this->race->participants()
            ->confirmed()
            ->with('racingCategory')
            ->orderBy('bib')
            ->get();

        $groups = empty($this->groups)
            ? $this->defaultGroups($separateWildcards)
            : $this->groups;

        return collect($groups)
            ->map(fn (array $keys) => $this->buildGroupFromKeys($keys, $participants, $separateWildcards))
            ->filter(fn (array $group) => $group['participants']->isNotEmpty())
            ->values();
    }

    private function shouldSeparateWildcards(): bool
    {
        return $this->separateWildcards && ($this->race->championship->wildcard?->enabled ?? false);
    }

    /**
     * One group per category, followed by its wildcard group when wildcards are separated.
     *
     * @return array<int, array<int, string>>
     */
    private function defaultGroups(bool $separateWildcards): array
    {
        return $this->race->championship->categories
            ->flatMap(fn (Category $category) => $separateWildcards
                ? [[$category->ulid], [$category->ulid.self::WILDCARD_SUFFIX]]
                : [[$category->ulid]])
            ->all();
    }

    /**
     * @param  array<int, string>  $keys
     * @param  Collection<int, \App\Models\Participant>  $participants
     */
    private function buildGroupFromKeys(array $keys, Collection $participants, bool $separateWildcards): array
    {
        $entries = $this->race->championship->categories
            ->flatMap(function (Category $category) use ($keys, $separateWildcards) {
                $entries = [];

                if (in_array($category->ulid, $keys, true)) {
                    $entries[] = ['category' => $category, 'wildcard' => $separateWildcards ? false : null];
                }

                if ($separateWildcards && in_array($category->ulid.self::WILDCARD_SUFFIX, $keys, true)) {
                    $entries[] = ['category' => $category, 'wildcard' => true];
                }

                return $entries;
            });

        $title = $entries
            ->map(fn (array $entry) => $entry['wildcard'] ? $entry['category']->name.' - '.__('Wildcard') : $entry['category']->name)
            ->join(' / ');

        $groupParticipants = $entries
            ->flatMap(fn (array $entry) => $participants
                ->where('category_id', $entry['category']->getKey())
                ->when(! is_null($entry['wildcard']), fn (Collection $filtered) => $filtered->filter(
                    fn ($participant) => (bool) $participant->wildcard === $entry['wildcard']
                )))
            ->values();

        return $this->buildGroup($title, $groupParticipants, $entries->count() > 1);
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
