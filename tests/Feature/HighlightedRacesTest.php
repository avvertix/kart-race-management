<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Race;
use App\View\Components\HighlightedRaces;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HighlightedRacesTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_next_race_shown()
    {
        $races = $this->createRaces();

        $this->travelTo(Carbon::parse('2022-12-29 12:00'));

        $view = $this->component(HighlightedRaces::class);

        $this->travelBack();

        $view->assertSeeText($races[1]->title);
        $view->assertSee(route('races.show', $races[1]));

        $view->assertSeeText($races[2]->title);
        $view->assertSee(route('races.show', $races[2]));

        $view->assertDontSeeText(__('No race available for self registration open or currently active.'));
    }

    public function test_next_race_in_championship_shown()
    {
        $races = $this->createRaces();

        $this->travelTo(Carbon::parse('2022-12-29 10:00'));

        $view = $this->component(HighlightedRaces::class, ['championship' => $races[1]->championship]);

        $this->travelBack();

        $view->assertSeeText($races[1]->title);
        $view->assertSee(route('races.show', $races[1]));
        $view->assertDontSeeText($races[2]->title);
        $view->assertDontSee(route('races.show', $races[2]));
        $view->assertDontSeeText(__('No race available for self registration open or currently active.'));
    }

    public function test_no_race_shown()
    {
        $races = $this->createRaces();

        $this->travelTo(Carbon::parse('2022-12-10 10:00'));

        $view = $this->component(HighlightedRaces::class);

        $this->travelBack();

        $view->assertSeeText(__('No race available for self registration open or currently active.'));

    }

    protected function createRaces()
    {
        $races = Race::factory()
            ->count(3)
            ->state(new Sequence(
                [
                    'event_start_at' => Carbon::parse('2022-12-28 09:00'), 'event_end_at' => Carbon::parse('2022-12-28 18:00'),
                    'registration_opens_at' => Carbon::parse('2022-12-26 09:00'), 'registration_closes_at' => Carbon::parse('2022-12-28 08:00'),
                ],
                [
                    'event_start_at' => Carbon::parse('2022-12-30 00:00'), 'event_end_at' => Carbon::parse('2022-12-30 23:59'),
                    'registration_opens_at' => Carbon::parse('2022-12-29 09:00'), 'registration_closes_at' => Carbon::parse('2022-12-29 23:00'),
                ],
                [
                    'event_start_at' => Carbon::parse('2022-12-30 09:00'), 'event_end_at' => Carbon::parse('2022-12-30 23:59'),
                    'registration_opens_at' => Carbon::parse('2022-12-29 11:00'), 'registration_closes_at' => Carbon::parse('2022-12-30 08:00'),
                ],
            ))
            ->create();

        return $races;
    }
}
