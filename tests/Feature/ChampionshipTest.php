<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ChampionshipTest extends TestCase
{
    use RefreshDatabase;


    public function test_championships_screen_can_be_rendered()
    {
        $user = User::factory()->organizer()->create();

        $champs = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.index'));

        $response->assertStatus(200);
    }

    public function test_new_championship_can_be_created()
    {
        $user = User::factory()->organizer()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.create'))
            ->post(route('championships.store'), [
                'start' => '2023-03-02',
                'end' => '2023-12-02',
                'title' => 'Kartsport 2023',
                'description' => 'a little description',
            ]);

        $response->assertStatus(200);

        $champ = Championship::first();

        $this->assertInstanceOf(Championship::class, $champ);
        $this->assertEquals('Kartsport 2023', $champ->title);
        $this->assertEquals('a little description', $champ->description);
        $this->assertEquals(Carbon::parse('2023-03-02'), $champ->start_at);
        $this->assertEquals(Carbon::parse('2023-12-02'), $champ->end_at);
    }
}
