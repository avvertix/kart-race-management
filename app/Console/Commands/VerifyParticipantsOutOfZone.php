<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ItalianRegion;
use App\Models\ItalianPostalCode;
use App\Models\Participant;
use App\Models\Race;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class VerifyParticipantsOutOfZone extends Command
{
    protected $signature = 'participants:verify-out-of-zone
                            {race : The UUID of the race}
                            {source : Path to the official national body verification JSON file (verificati export)}
                            {--output= : Path to write the JSON discrepancy report (default: storage/app/out-of-zone-discrepancies-{race}.json)}';

    protected $description = 'Compare the out-of-zone status stored for a race against the official national body verification data';

    public function handle(): int
    {
        $race = Race::where('uuid', $this->argument('race'))->first();

        if (! $race) {
            $this->error("Race [{$this->argument('race')}] not found.");

            return Command::FAILURE;
        }

        if (! $race->hasZoneConfigured()) {
            $this->warn('This race has no zone configured, there is no out-of-zone status to verify.');

            return Command::SUCCESS;
        }

        $sourcePath = $this->argument('source');

        if (! is_file($sourcePath)) {
            $this->error("Source file [{$sourcePath}] not found.");

            return Command::FAILURE;
        }

        $entries = json_decode((string) file_get_contents($sourcePath), true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($entries)) {
            $this->error("Source file [{$sourcePath}] does not contain a valid JSON array.");

            return Command::FAILURE;
        }

        $participants = Participant::where('race_id', $race->id)->get();

        $participantsByBib = $participants->keyBy(fn (Participant $participant) => (string) $participant->bib);

        $participantsByLicence = $participants
            ->filter(fn (Participant $participant) => filled($participant->driverLicenceNumber))
            ->keyBy(fn (Participant $participant) => $this->normalizeLicenceKey($participant->driverLicenceNumber));

        $discrepancies = [];
        $unmatchedEntries = [];
        $skipped = 0;
        $duplicates = 0;
        $matched = 0;
        $processedParticipantUuids = [];

        foreach ($entries as $entry) {
            if ((int) ($entry['flagAnnullamento'] ?? 0) !== 0) {
                $skipped++;

                continue;
            }

            $conduttore = $entry['conduttore1'] ?? null;
            $bib = $entry['carBean']['numeroDiGara'] ?? null;

            if ($conduttore === null || $bib === null) {
                $skipped++;

                continue;
            }

            // The national body export can list multiple rows for the same driver (one per
            // race session/run), and a BIB can occasionally be reused across unrelated rows.
            // Matching by licence number first (a stable per-driver identifier) avoids pairing
            // a participant with the wrong row; BIB is only a fallback for foreign-licence
            // drivers or missing licence data.
            $nationalLicenceNumber = $conduttore['licenza']['id'] ?? $conduttore['idLicenzaEstera'] ?? null;
            $licenceKey = filled($nationalLicenceNumber) ? $this->normalizeLicenceKey($nationalLicenceNumber) : null;

            $participant = $licenceKey !== null ? $participantsByLicence->get($licenceKey) : null;
            $participantMatchedBy = $participant ? 'licence' : null;

            if (! $participant) {
                $participant = $participantsByBib->get((string) $bib);
                $participantMatchedBy = $participant ? 'bib' : null;
            }

            if (! $participant) {
                $unmatchedEntries[] = [
                    'bib' => $bib,
                    'name' => $conduttore['denominazione'] ?? null,
                    'reason' => 'No participant found for this BIB or licence number in the race.',
                ];

                continue;
            }

            if (in_array($participant->uuid, $processedParticipantUuids, true)) {
                $duplicates++;

                continue;
            }

            $processedParticipantUuids[] = $participant->uuid;
            $matched++;

            [$expectedOutOfZone, $region, $matchedBy, $province, $postalCode, $city] = $this->resolveExpectedOutOfZone($race, $conduttore);

            $storedOutOfZone = $participant->wasProcessedForOutOfZone() ? $participant->isOutOfZone() : null;
            $storedRegion = $participant->region;

            $outOfZoneStatusMatches = $storedOutOfZone === $expectedOutOfZone;
            // The out-of-zone flag can match by coincidence (e.g. both the stored and the
            // official region are outside the configured zone) while the underlying region
            // is still wrong, typically because the driver declared an incorrect residence
            // address. Flag that case too, even though the boolean status agrees.
            $regionMatches = $storedRegion === $region;

            if ($outOfZoneStatusMatches && $regionMatches) {
                continue;
            }

            $storedLicenceNumber = $participant->driverLicenceNumber;

            $discrepancies[] = [
                'bib' => $bib,
                'participant_uuid' => $participant->uuid,
                'name' => "{$participant->first_name} {$participant->last_name}",
                'category' => $participant->racingCategory?->short_name ?? $participant->racingCategory?->name,
                'nationality' => $participant->driver['nationality'] ?? null,
                'participant_matched_by' => $participantMatchedBy,
                'stored_licence_number' => $storedLicenceNumber,
                'national_body_licence_number' => $nationalLicenceNumber,
                'licence_number_mismatch' => filled($nationalLicenceNumber) && filled($storedLicenceNumber)
                    && $this->normalizeLicenceKey($nationalLicenceNumber) !== $this->normalizeLicenceKey($storedLicenceNumber),
                'stored_out_of_zone' => $storedOutOfZone,
                'expected_out_of_zone' => $expectedOutOfZone,
                'stored_region' => $storedRegion?->value,
                'region' => $region?->value,
                'region_mismatch' => ! $regionMatches,
                'matched_region_by' => $matchedBy,
                'province' => $province,
                'postal_code' => $postalCode,
                'city' => $city,
            ];
        }

        if (empty($discrepancies)) {
            $this->info('No discrepancies found, out-of-zone status matches the official national body data.');
        } else {
            $this->table(
                ['Bib', 'Name', 'Category', 'Nationality', 'Matched by', 'Stored', 'Expected', 'Stored region', 'Official region', 'Zone matched by', 'Region mismatch', 'Licence mismatch'],
                collect($discrepancies)->map(fn (array $row) => [
                    $row['bib'],
                    $row['name'],
                    $row['category'] ?? '—',
                    $row['nationality'] ?? '—',
                    $row['participant_matched_by'] ?? '—',
                    $this->formatOutOfZone($row['stored_out_of_zone']),
                    $this->formatOutOfZone($row['expected_out_of_zone']),
                    $row['stored_region'] ?? '—',
                    $row['region'] ?? '—',
                    $row['matched_region_by'] ?? '—',
                    $row['region_mismatch'] ? '<comment>yes</comment>' : 'no',
                    $row['licence_number_mismatch'] ? '<comment>yes</comment>' : 'no',
                ])
            );
        }

        $this->newLine();
        $this->line("Checked {$matched} matched participant(s), {$skipped} entries skipped, {$duplicates} duplicate entries skipped, ".count($unmatchedEntries).' entries unmatched.');
        $this->line(count($discrepancies).' discrepancy(ies) found.');

        if (! empty($unmatchedEntries)) {
            $this->warn(count($unmatchedEntries).' national body entries had no matching participant by licence number or BIB in this race.');
        }

        $outputPath = $this->option('output') ?? storage_path("app/out-of-zone-discrepancies-{$race->uuid}.json");

        file_put_contents($outputPath, json_encode([
            'race' => $race->uuid,
            'race_title' => $race->title,
            'checked_at' => Carbon::now()->toIso8601String(),
            'source_file' => $sourcePath,
            'matched_entries' => $matched,
            'skipped_entries' => $skipped,
            'duplicate_entries_skipped' => $duplicates,
            'unmatched_entries' => $unmatchedEntries,
            'discrepancies' => $discrepancies,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->info("Report written to [{$outputPath}].");

        return Command::SUCCESS;
    }

    /**
     * Resolve the expected out-of-zone status for a national body "conduttore1" record,
     * mirroring the logic in App\Actions\DetermineParticipantZone.
     *
     * @param  array<string, mixed>  $conduttore
     * @return array{0: bool, 1: ?ItalianRegion, 2: ?string, 3: ?string, 4: ?string, 5: ?string}
     */
    private function resolveExpectedOutOfZone(Race $race, array $conduttore): array
    {
        // Drivers without a national licence (foreign licence) are out of zone by definition.
        if (filled($conduttore['idLicenzaEstera'] ?? null)) {
            return [true, null, 'foreign_licence', null, null, null];
        }

        $province = $conduttore['indirizzo']['provincia']['sigla'] ?? $conduttore['indirizzo']['provincia']['label'] ?? null;
        $postalCode = $conduttore['indirizzo']['cap'] ?? null;
        $city = $conduttore['indirizzo']['localita'] ?? null;

        $region = null;
        $matchedBy = null;

        if (filled($province)) {
            $region = ItalianRegion::fromProvince($province);
            $matchedBy = $region ? 'province' : null;
        }

        if ($region === null && filled($postalCode)) {
            $region = ItalianPostalCode::findRegionByCap(mb_trim($postalCode));
            $matchedBy = $region ? 'cap' : $matchedBy;
        }

        if ($region === null && filled($city)) {
            $region = ItalianPostalCode::whereRaw('UPPER(municipality) = ?', [mb_strtoupper(mb_trim($city))])->first()?->region;
            $matchedBy = $region ? 'city' : $matchedBy;
        }

        $expectedOutOfZone = $region === null
            || ! in_array($region->value, $race->zone_regions?->toArray() ?? [], true);

        return [$expectedOutOfZone, $region, $matchedBy, $province, $postalCode, $city];
    }

    private function formatOutOfZone(?bool $value): string
    {
        if ($value === null) {
            return 'not evaluated';
        }

        return $value ? 'out of zone' : 'in zone';
    }

    private function normalizeLicenceKey(string|int $value): string
    {
        return mb_strtoupper(mb_trim((string) $value));
    }
}
