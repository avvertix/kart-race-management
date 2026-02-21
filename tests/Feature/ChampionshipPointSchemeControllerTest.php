<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\PointsConfigData;
use App\Models\Championship;
use App\Models\ChampionshipPointScheme;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ChampionshipPointSchemeControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_listing_point_schemes_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.point-schemes.index', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_creating_point_schemes_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.point-schemes.create', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_point_schemes_can_be_listed(): void
    {
        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()
            ->has(ChampionshipPointScheme::factory()->count(2), 'pointSchemes')
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.point-schemes.index', $championship));

        $response->assertSuccessful();

        $response->assertViewIs('point-scheme.index');

        $response->assertViewHas('pointSchemes', $championship->pointSchemes()->orderBy('name', 'ASC')->get());
    }

    public function test_point_scheme_creation_requires_organizer_level(): void
    {
        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.point-schemes.create', $championship));

        $response->assertForbidden();
    }

    public function test_point_scheme_creation_form_shown(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.point-schemes.create', $championship));

        $response->assertSuccessful();

        $response->assertViewIs('point-scheme.create');

        $response->assertViewHas('championship', $championship);
    }

    public function test_point_scheme_created(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $pointsConfig = $this->samplePointsConfig();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.point-schemes.create', $championship))
            ->post(route('championships.point-schemes.store', $championship), [
                'name' => 'Standard Points',
                'points_config' => $pointsConfig,
            ]);

        $response->assertRedirectToRoute('championships.point-schemes.index', $championship);

        $response->assertSessionHas('flash.banner', 'Standard Points created.');

        $pointScheme = ChampionshipPointScheme::first();

        $this->assertInstanceOf(ChampionshipPointScheme::class, $pointScheme);
        $this->assertInstanceOf(PointsConfigData::class, $pointScheme->points_config);

        $this->assertEquals('Standard Points', $pointScheme->name);
        $this->assertEquals($pointsConfig, $pointScheme->points_config->toConfig());
    }

    public function test_point_scheme_name_unique_within_championship(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        ChampionshipPointScheme::factory()->create([
            'championship_id' => $championship->getKey(),
            'name' => 'Standard Points',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('championships.point-schemes.create', $championship))
            ->post(route('championships.point-schemes.store', $championship), [
                'name' => 'Standard Points',
                'points_config' => $this->samplePointsConfig(),
            ]);

        $response->assertRedirectToRoute('championships.point-schemes.create', $championship);

        $response->assertSessionHasErrors('name');
    }

    public function test_point_scheme_created_with_same_name_in_another_championship(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        ChampionshipPointScheme::factory()->create([
            'name' => 'Standard Points',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('championships.point-schemes.create', $championship))
            ->post(route('championships.point-schemes.store', $championship), [
                'name' => 'Standard Points',
                'points_config' => $this->samplePointsConfig(),
            ]);

        $response->assertRedirectToRoute('championships.point-schemes.index', $championship);

        $response->assertSessionHas('flash.banner', 'Standard Points created.');

        $pointScheme = $championship->pointSchemes()->first();

        $this->assertInstanceOf(ChampionshipPointScheme::class, $pointScheme);

        $this->assertEquals('Standard Points', $pointScheme->name);
    }

    public function test_point_scheme_edit_form_shown(): void
    {
        $user = User::factory()->organizer()->create();

        $pointScheme = ChampionshipPointScheme::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('point-schemes.edit', $pointScheme));

        $response->assertSuccessful();

        $response->assertViewIs('point-scheme.edit');

        $response->assertViewHas('pointScheme', $pointScheme);

        $response->assertViewHas('championship', $pointScheme->championship);
    }

    public function test_point_scheme_updated(): void
    {
        $user = User::factory()->organizer()->create();

        $pointScheme = ChampionshipPointScheme::factory()
            ->create();

        $defaultStatuses = [
            '20' => ['mode' => 'fixed', 'points' => 0],
            '30' => ['mode' => 'fixed', 'points' => 0],
            '40' => ['mode' => 'fixed', 'points' => 0],
        ];

        $updatedConfig = [
            '20' => ['positions' => [5, 3, 1], 'statuses' => $defaultStatuses],
            '30' => ['positions' => [30, 20, 15, 10, 8, 6, 4, 2, 1], 'statuses' => $defaultStatuses],
            '40' => ['positions' => [30, 20, 15, 10, 8, 6, 4, 2, 1], 'statuses' => $defaultStatuses],
        ];

        $response = $this
            ->actingAs($user)
            ->from(route('point-schemes.edit', $pointScheme))
            ->put(route('point-schemes.update', $pointScheme), [
                'name' => 'Updated Points',
                'points_config' => $updatedConfig,
            ]);

        $response->assertRedirectToRoute('championships.point-schemes.index', $pointScheme->championship);

        $response->assertSessionHas('flash.banner', 'Updated Points updated.');

        $updatedPointScheme = $pointScheme->fresh();

        $this->assertInstanceOf(ChampionshipPointScheme::class, $updatedPointScheme);

        $this->assertEquals('Updated Points', $updatedPointScheme->name);
        $this->assertEquals($updatedConfig, $updatedPointScheme->points_config->toConfig());
    }

    public function test_point_scheme_created_with_ranked_status(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $pointsConfig = [
            '20' => [
                'positions' => [3, 2, 1],
                'statuses' => [
                    '20' => ['mode' => 'ranked'],
                    '30' => ['mode' => 'ranked'],
                    '40' => ['mode' => 'fixed', 'points' => 0],
                ],
            ],
            '30' => [
                'positions' => [25, 18, 15, 12, 10, 8, 6, 4, 2, 1],
                'statuses' => [
                    '20' => ['mode' => 'fixed', 'points' => 2],
                    '30' => ['mode' => 'ranked'],
                    '40' => ['mode' => 'fixed', 'points' => 0],
                ],
            ],
            '40' => [
                'positions' => [25, 18, 15, 12, 10, 8, 6, 4, 2, 1],
                'statuses' => [
                    '20' => ['mode' => 'fixed', 'points' => 0],
                    '30' => ['mode' => 'fixed', 'points' => 0],
                    '40' => ['mode' => 'fixed', 'points' => 0],
                ],
            ],
        ];

        $response = $this
            ->actingAs($user)
            ->from(route('championships.point-schemes.create', $championship))
            ->post(route('championships.point-schemes.store', $championship), [
                'name' => 'Ranked Scheme',
                'points_config' => $pointsConfig,
            ]);

        $response->assertRedirectToRoute('championships.point-schemes.index', $championship);

        $pointScheme = ChampionshipPointScheme::first();

        $this->assertInstanceOf(ChampionshipPointScheme::class, $pointScheme);
        $this->assertInstanceOf(PointsConfigData::class, $pointScheme->points_config);

        $this->assertTrue($pointScheme->isStatusRanked(
            \App\Models\RunType::QUALIFY,
            \App\Models\ResultStatus::DID_NOT_START,
        ));
        $this->assertFalse($pointScheme->isStatusRanked(
            \App\Models\RunType::RACE_2,
            \App\Models\ResultStatus::DID_NOT_START,
        ));
    }

    /**
     * @return array<string, array{positions: list<int|float>, statuses: array<string, array{mode: string, points?: int|float}>}>
     */
    private function samplePointsConfig(): array
    {
        $defaultStatuses = [
            '20' => ['mode' => 'fixed', 'points' => 0],
            '30' => ['mode' => 'fixed', 'points' => 0],
            '40' => ['mode' => 'fixed', 'points' => 0],
        ];

        return [
            '20' => ['positions' => [3, 2, 1], 'statuses' => $defaultStatuses],
            '30' => ['positions' => [25, 18, 15, 12, 10, 8, 6, 4, 2, 1], 'statuses' => $defaultStatuses],
            '40' => ['positions' => [25, 18, 15, 12, 10, 8, 6, 4, 2, 1], 'statuses' => $defaultStatuses],
        ];
    }
}
