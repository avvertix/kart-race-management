<?php

namespace Tests\Feature;

use App\Actions\GenerateRaceNumber;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Participant;
use App\Models\Race;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GenerateRaceNumberTest extends TestCase
{
    use RefreshDatabase;

    
    public function test_race_numbers_suggested(): void
    {
        $championship = Championship::factory()->create();

        $numbers = (new GenerateRaceNumber())($championship);

        $this->assertEquals([1,2,3,4], $numbers);
    }
    
    public function test_suggestion_respect_given_amount(): void
    {
        $championship = Championship::factory()->create();

        $numbers = (new GenerateRaceNumber())($championship, 2);

        $this->assertEquals([1,2], $numbers);
    }
    
    public function test_requested_amount_considered_as_absolute_value(): void
    {
        $championship = Championship::factory()->create();

        $numbers = (new GenerateRaceNumber())($championship, -2);

        $this->assertEquals([1,2], $numbers);
    }
    
    public function test_suggestion_dont_include_participants(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory()->count(2))
            ->create();

        $firstRace = $championship->races->first();
        $lastRace = $championship->races->last();
        
        $participationToFirstRace = Participant::factory()
            ->for($firstRace)
            ->for($championship)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'LN1',
                'bib' => 1,
            ])
            ->create();

        $participationToLastRace = Participant::factory()
            ->for($lastRace)
            ->for($championship)
            ->driver([
                'first_name' => 'Jet',
                'last_name' => 'Racer',
                'licence_number' => 'LN2',
                'bib' => 2,
            ])
            ->create();

        $numbers = (new GenerateRaceNumber())($championship);

        $this->assertEquals([3,4,5,6], $numbers);
    }
    
    public function test_reservations_not_suggested(): void
    {
        $championship = Championship::factory()
            ->has(BibReservation::factory()->withLicence()->state(['bib' => 1]), 'reservations')
            ->create();

        $numbers = (new GenerateRaceNumber())($championship);

        $this->assertEquals([2,3,4,5], $numbers);
    }
    
    public function test_suggestion_tops_at_a_thousand(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory())
            ->create();
        
        $participationToFirstRace = Participant::factory()
            ->for($championship->races->first())
            ->for($championship)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'LN1',
                'bib' => 1000,
            ])
            ->create();

        $numbers = (new GenerateRaceNumber())($championship, 1000);

        $this->assertCount(999, $numbers);
        $this->assertEquals(1, $numbers[0]);
        $this->assertEquals(999, $numbers[998]);
    }
    
    public function test_suggestion_is_specific_to_given_championship(): void
    {
        $championship = Championship::factory()
            ->create();
        
        Participant::factory()
            ->driver([
                'bib' => 1,
            ])
            ->create();

        $numbers = (new GenerateRaceNumber())($championship);

        $this->assertEquals([1,2,3,4], $numbers);
    }
}
