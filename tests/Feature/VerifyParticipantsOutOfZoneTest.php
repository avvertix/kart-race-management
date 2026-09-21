<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ItalianRegion;
use App\Models\Category;
use App\Models\ItalianPostalCode;
use App\Models\Participant;
use App\Models\Race;
use App\Models\RaceType;
use Illuminate\Support\Facades\File;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class VerifyParticipantsOutOfZoneTest extends TestCase
{
    use FastRefreshDatabase;

    /** @var array<int, string> */
    private array $writtenFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->writtenFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_fails_with_invalid_race_uuid(): void
    {
        $source = $this->writeJsonFixture([]);

        $this->artisan('participants:verify-out-of-zone', [
            'race' => 'invalid-uuid',
            'source' => $source,
        ])->assertFailed();
    }

    public function test_warns_when_race_has_no_zone_configured(): void
    {
        $race = Race::factory()->create(['type' => RaceType::NATIONAL->value, 'zone_regions' => null]);
        $source = $this->writeJsonFixture([]);

        $this->artisan('participants:verify-out-of-zone', [
            'race' => $race->uuid,
            'source' => $source,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('no zone configured');
    }

    public function test_fails_when_source_file_does_not_exist(): void
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $this->artisan('participants:verify-out-of-zone', [
            'race' => $race->uuid,
            'source' => '/non/existent/file.json',
        ])->assertFailed();
    }

    public function test_fails_when_source_file_is_not_valid_json_array(): void
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'verificati').'.json';
        File::put($path, '"not-an-array"');
        $this->writtenFiles[] = $path;

        $this->artisan('participants:verify-out-of-zone', [
            'race' => $race->uuid,
            'source' => $path,
        ])->assertFailed();
    }

    public function test_reports_no_discrepancy_when_status_matches(): void
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $participant = Participant::factory()->recycle($race)->recycle($race->championship)
            ->create(['bib' => 8, 'region' => ItalianRegion::LOMBARDIA])
            ->fresh();
        $participant->markOutOfZone(false);

        $source = $this->writeJsonFixture([
            $this->conduttoreEntry(8),
        ]);

        $output = tempnam(sys_get_temp_dir(), 'report').'.json';
        $this->writtenFiles[] = $output;

        $this->artisan('participants:verify-out-of-zone', [
            'race' => $race->uuid,
            'source' => $source,
            '--output' => $output,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('No discrepancies found');

        $report = json_decode(File::get($output), true);
        $this->assertSame([], $report['discrepancies']);
    }

    public function test_detects_discrepancy_when_stored_status_differs_from_province(): void
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $category = Category::factory()->recycle($race->championship)->create(['name' => 'Junior', 'short_name' => null]);

        // Stored as in-zone, but the national body address (MI/Lombardia) is in the zone,
        // so the stored "out of zone" flag is wrong.
        $participant = Participant::factory()->recycle($race)->recycle($race->championship)->category($category)->create(['bib' => 21])->fresh();
        $participant->markOutOfZone(true);

        $source = $this->writeJsonFixture([
            $this->conduttoreEntry(21),
        ]);

        $output = tempnam(sys_get_temp_dir(), 'report').'.json';
        $this->writtenFiles[] = $output;

        $this->artisan('participants:verify-out-of-zone', [
            'race' => $race->uuid,
            'source' => $source,
            '--output' => $output,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('1 discrepancy(ies) found.');

        $report = json_decode(File::get($output), true);
        $this->assertCount(1, $report['discrepancies']);
        $this->assertSame(21, $report['discrepancies'][0]['bib']);
        $this->assertSame('Junior', $report['discrepancies'][0]['category']);
        $this->assertSame('Italy', $report['discrepancies'][0]['nationality']);
        $this->assertTrue($report['discrepancies'][0]['stored_out_of_zone']);
        $this->assertFalse($report['discrepancies'][0]['expected_out_of_zone']);
        $this->assertSame('lombardia', $report['discrepancies'][0]['region']);
        $this->assertSame('province', $report['discrepancies'][0]['matched_region_by']);
    }

    public function test_foreign_licence_is_expected_out_of_zone_by_definition(): void
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $participant = Participant::factory()->recycle($race)->recycle($race->championship)->create(['bib' => 5])->fresh();
        $participant->markOutOfZone(false);

        $source = $this->writeJsonFixture([
            $this->conduttoreEntry(5, [
                'idLicenzaEstera' => '461974',
                'licenza' => null,
                'indirizzo' => ['localita' => 'PIACENZA'],
            ]),
        ]);

        $output = tempnam(sys_get_temp_dir(), 'report').'.json';
        $this->writtenFiles[] = $output;

        $this->artisan('participants:verify-out-of-zone', [
            'race' => $race->uuid,
            'source' => $source,
            '--output' => $output,
        ])->assertSuccessful();

        $report = json_decode(File::get($output), true);
        $this->assertCount(1, $report['discrepancies']);
        $this->assertTrue($report['discrepancies'][0]['expected_out_of_zone']);
        $this->assertSame('foreign_licence', $report['discrepancies'][0]['matched_region_by']);
    }

    public function test_falls_back_to_cap_and_city_when_province_unknown(): void
    {
        ItalianPostalCode::create([
            'cap' => '00100',
            'province_code' => 'RM',
            'province' => 'Roma',
            'municipality' => 'Roma',
            'region' => ItalianRegion::LAZIO->value,
        ]);

        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $participant = Participant::factory()->recycle($race)->recycle($race->championship)->create(['bib' => 9])->fresh();
        $participant->markOutOfZone(false);

        $source = $this->writeJsonFixture([
            $this->conduttoreEntry(9, [
                'indirizzo' => ['cap' => '00100', 'localita' => 'ROMA', 'provincia' => null],
            ]),
        ]);

        $output = tempnam(sys_get_temp_dir(), 'report').'.json';
        $this->writtenFiles[] = $output;

        $this->artisan('participants:verify-out-of-zone', [
            'race' => $race->uuid,
            'source' => $source,
            '--output' => $output,
        ])->assertSuccessful();

        $report = json_decode(File::get($output), true);
        $this->assertCount(1, $report['discrepancies']);
        $this->assertSame('lazio', $report['discrepancies'][0]['region']);
        $this->assertSame('cap', $report['discrepancies'][0]['matched_region_by']);
        $this->assertTrue($report['discrepancies'][0]['expected_out_of_zone']);
    }

    public function test_warns_about_unmatched_entries_by_bib(): void
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $source = $this->writeJsonFixture([
            $this->conduttoreEntry(999),
        ]);

        $this->artisan('participants:verify-out-of-zone', [
            'race' => $race->uuid,
            'source' => $source,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('1 national body entries had no matching participant');
    }

    public function test_skips_annulled_entries(): void
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $participant = Participant::factory()->recycle($race)->recycle($race->championship)->create(['bib' => 3])->fresh();
        $participant->markOutOfZone(true);

        $entry = $this->conduttoreEntry(3);
        $entry['flagAnnullamento'] = 1;

        $source = $this->writeJsonFixture([$entry]);

        $this->artisan('participants:verify-out-of-zone', [
            'race' => $race->uuid,
            'source' => $source,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('No discrepancies found');
    }

    public function test_matches_by_licence_number_and_ignores_duplicate_bib_entries(): void
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $participant = Participant::factory()
            ->recycle($race)
            ->recycle($race->championship)
            ->driver(['licence_number' => '492209'])
            ->create(['bib' => 9, 'region' => ItalianRegion::LOMBARDIA])
            ->fresh();
        $participant->markOutOfZone(false);

        // The national body export lists two rows for BIB 9 (e.g. one per race session),
        // both for the same driver/licence. The second row has broken address data and
        // must be ignored, not override the correct in-zone result from the first row.
        $firstRun = $this->conduttoreEntry(9, ['licenza' => ['id' => 492209]]);
        $secondRun = $this->conduttoreEntry(9, ['licenza' => ['id' => 492209], 'indirizzo' => []]);

        $source = $this->writeJsonFixture([$firstRun, $secondRun]);

        $output = tempnam(sys_get_temp_dir(), 'report').'.json';
        $this->writtenFiles[] = $output;

        $this->artisan('participants:verify-out-of-zone', [
            'race' => $race->uuid,
            'source' => $source,
            '--output' => $output,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('No discrepancies found');

        $report = json_decode(File::get($output), true);
        $this->assertSame([], $report['discrepancies']);
        $this->assertSame(1, $report['matched_entries']);
        $this->assertSame(1, $report['duplicate_entries_skipped']);
    }

    public function test_flags_region_mismatch_even_when_out_of_zone_status_matches(): void
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value, ItalianRegion::VENETO->value],
        ]);

        // Stored as in-zone via a Lombardia residence, but the official national body address
        // is actually in Veneto. Still "in zone" overall (both regions are in the configured
        // zone), so the out-of-zone boolean matches — but the underlying region is wrong,
        // which previously went unnoticed.
        $participant = Participant::factory()->recycle($race)->recycle($race->championship)
            ->create(['bib' => 15, 'region' => ItalianRegion::LOMBARDIA])
            ->fresh();
        $participant->markOutOfZone(false);

        $source = $this->writeJsonFixture([
            $this->conduttoreEntry(15, [
                'indirizzo' => [
                    'cap' => '30100',
                    'localita' => 'VENEZIA',
                    'provincia' => ['sigla' => 'VE', 'label' => 'VENEZIA'],
                ],
            ]),
        ]);

        $output = tempnam(sys_get_temp_dir(), 'report').'.json';
        $this->writtenFiles[] = $output;

        $this->artisan('participants:verify-out-of-zone', [
            'race' => $race->uuid,
            'source' => $source,
            '--output' => $output,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('1 discrepancy(ies) found.');

        $report = json_decode(File::get($output), true);
        $this->assertCount(1, $report['discrepancies']);
        $this->assertFalse($report['discrepancies'][0]['stored_out_of_zone']);
        $this->assertFalse($report['discrepancies'][0]['expected_out_of_zone']);
        $this->assertSame('lombardia', $report['discrepancies'][0]['stored_region']);
        $this->assertSame('veneto', $report['discrepancies'][0]['region']);
        $this->assertTrue($report['discrepancies'][0]['region_mismatch']);
    }

    private function writeJsonFixture(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'verificati').'.json';
        File::put($path, json_encode($entries));
        $this->writtenFiles[] = $path;

        return $path;
    }

    private function conduttoreEntry(int $bib, array $conduttoreOverrides = []): array
    {
        return [
            'flagAnnullamento' => 0,
            'carBean' => ['numeroDiGara' => $bib],
            'conduttore1' => array_merge([
                'denominazione' => 'Nome Cognome',
                'licenza' => ['id' => 492209],
                'indirizzo' => [
                    'cap' => '20100',
                    'localita' => 'MILANO',
                    'provincia' => ['sigla' => 'MI', 'label' => 'MILANO'],
                ],
            ], $conduttoreOverrides),
        ];
    }
}
