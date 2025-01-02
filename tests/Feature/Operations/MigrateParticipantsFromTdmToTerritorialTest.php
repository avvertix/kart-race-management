<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Models\Participant;
use App\Models\Race;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigrateParticipantsFromTdmToTerritorialTest extends TestCase
{
    use RefreshDatabase;

    public function test_participants_in_tmd_categories_are_updated()
    {

        config([
            'categories.default' => [
                '125 SENIOR TDM ROK' => [
                    'name' => '125 Senior Rok (TDM)',
                    'tires' => 'MG_SM',
                    'timekeeper_label' => '125 TAG SENIOR TERR',
                    'enabled' => false,
                ],

                '125 SENIOR TDM SUPEROK' => [
                    'name' => '125 Senior Superok (TDM)',
                    'tires' => 'MG_SM',
                    'timekeeper_label' => '125 TAG SENIOR TERR',
                    'enabled' => false,
                ],

                '125 SENIOR TERR' => [
                    'name' => '125 Senior Territorial',
                    'description' => 'For all engine trophies including TDM (e.g. X30, Rok, Rotax, ...)',
                    'tires' => 'MG_SM',
                    'timekeeper_label' => '125 TAG SENIOR TERR',
                ],
            ],
        ]);

        $race = Race::factory()
            ->create([
                'event_start_at' => Carbon::parse('2023-02-28'),
                'title' => 'Race title',
            ]);

        $actual_participant = Participant::factory()->create([
            'category' => '125 SENIOR TDM SUPEROK',
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship->getKey(),
        ]);

        $this->artisan('operations:process', [
            '--test' => true,
            '2023_04_21_120712_move_participant_from_tdm_to_territorial',
        ])
            ->assertSuccessful();

        $participant = $actual_participant->fresh();

        $lastActivity = $participant->activities()->get()->last();

        $this->assertEquals('125 SENIOR TERR', $participant->category);
        $this->assertEquals('updated', $lastActivity->event);
        $this->assertEquals('participant', $lastActivity->subject_type);
        $this->assertEquals(['category' => '125 SENIOR TERR'], $lastActivity->changes()->get('attributes'));
        $this->assertEquals(['category' => '125 SENIOR TDM SUPEROK'], $lastActivity->changes()->get('old'));

    }

    public function test_participants_not_in_tmd_categories_are_left_intact()
    {

        config([
            'categories.default' => [
                '125 SENIOR TDM ROK' => [
                    'name' => '125 Senior Rok (TDM)',
                    'tires' => 'MG_SM',
                    'timekeeper_label' => '125 TAG SENIOR TERR',
                    'enabled' => false,
                ],

                '125 SENIOR TDM SUPEROK' => [
                    'name' => '125 Senior Superok (TDM)',
                    'tires' => 'MG_SM',
                    'timekeeper_label' => '125 TAG SENIOR TERR',
                    'enabled' => false,
                ],

                '125 SENIOR TERR' => [
                    'name' => '125 Senior Territorial',
                    'description' => 'For all engine trophies including TDM (e.g. X30, Rok, Rotax, ...)',
                    'tires' => 'MG_SM',
                    'timekeeper_label' => '125 TAG SENIOR TERR',
                ],
            ],
        ]);

        $race = Race::factory()
            ->create([
                'event_start_at' => Carbon::parse('2023-02-28'),
                'title' => 'Race title',
            ]);

        $actual_participant = Participant::factory()->create([
            'category' => '125 SENIOR TERR',
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship->getKey(),
        ]);

        $this->artisan('operations:process', [
            '--test' => true,
            '2023_04_21_120712_move_participant_from_tdm_to_territorial',
        ])
            ->assertSuccessful();

        $participant = $actual_participant->fresh();

        $lastActivity = $participant->activities()->get()->last();

        $this->assertEquals('125 SENIOR TERR', $participant->category);
        $this->assertEquals('created', $lastActivity->event);
        $this->assertEquals('participant', $lastActivity->subject_type);
    }
}
