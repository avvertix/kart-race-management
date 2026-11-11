<?php

declare(strict_types=1);

namespace Tests\Feature\Wildcards;

use App\Actions\Enrollments\ConfirmEnrollment;
use App\Actions\Wildcard\AttributeWildcardBasedOnEnrollment;
use App\Features\LicencesAndEnrollments;
use App\Livewire\WildcardSettings;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\RunResult;
use App\Models\User;
use App\Models\WildcardStrategy;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class AttributeWildcardBasedOnEnrollmentTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    public function test_only_participants_with_a_confirmed_enrollment_score_points(): void
    {
        $championship = $this->championship();
        $race = Race::factory()->recycle($championship)->create();

        $confirmed = Licence::factory()->verified()->create(['number' => 'CONFIRMED-1']);
        Enrollment::factory()->confirmed()->recycle($championship)->create(['licence_id' => $confirmed->getKey()]);

        $submitted = Licence::factory()->verified()->create(['number' => 'SUBMITTED-1']);
        Enrollment::factory()->recycle($championship)->create(['licence_id' => $submitted->getKey()]);

        $evaluate = new AttributeWildcardBasedOnEnrollment;

        $this->assertFalse($evaluate($this->participant($race, 'CONFIRMED-1'), $race));
        $this->assertTrue($evaluate($this->participant($race, 'SUBMITTED-1'), $race));
        $this->assertTrue($evaluate($this->participant($race, 'NOT-ENROLLED'), $race));
        $this->assertSame(WildcardStrategy::BASED_ON_ENROLLMENT, WildcardStrategy::from(40));
        $this->assertInstanceOf(AttributeWildcardBasedOnEnrollment::class, WildcardStrategy::BASED_ON_ENROLLMENT->resolve());
    }

    public function test_confirming_an_enrollment_reevaluates_races_without_results(): void
    {
        $championship = $this->championship();
        $raceWithResults = Race::factory()->recycle($championship)->create();
        RunResult::factory()->recycle($raceWithResults)->create();
        $nextRace = Race::factory()->recycle($championship)->create();

        $licence = Licence::factory()->verified()->create(['number' => 'MID-SEASON']);
        $enrollment = Enrollment::factory()->recycle($championship)->create(['licence_id' => $licence->getKey()]);

        $past = $this->participant($raceWithResults, 'MID-SEASON', wildcard: true);
        $next = $this->participant($nextRace, 'MID-SEASON', wildcard: true);
        $otherDriver = $this->participant($nextRace, 'SOMEONE-ELSE', wildcard: false);

        app(ConfirmEnrollment::class)($enrollment, User::factory()->organizer()->create());

        $this->assertTrue($past->fresh()->wildcard);
        $this->assertFalse($next->fresh()->wildcard);
        $this->assertFalse($otherDriver->fresh()->wildcard);
    }

    public function test_other_strategies_ignore_enrollments(): void
    {
        $championship = Championship::factory()->create([
            'wildcard' => ['enabled' => true, 'strategy' => WildcardStrategy::BASED_ON_BIB_RESERVATION],
            'enrollment' => ['enabled' => true, 'requires_competitor_when_underage' => false],
        ]);
        $race = Race::factory()->recycle($championship)->create();
        $licence = Licence::factory()->verified()->create(['number' => 'BIB-STRATEGY']);
        $enrollment = Enrollment::factory()->recycle($championship)->create(['licence_id' => $licence->getKey()]);
        $participant = $this->participant($race, 'BIB-STRATEGY', wildcard: true);

        app(ConfirmEnrollment::class)($enrollment, User::factory()->organizer()->create());

        $this->assertTrue($participant->fresh()->wildcard);
        $this->assertFalse((new AttributeWildcardBasedOnEnrollment)($participant, $race));
    }

    public function test_the_organizer_recalculates_the_wildcards_of_a_race(): void
    {
        $championship = $this->championship();
        $race = Race::factory()->recycle($championship)->create();
        RunResult::factory()->recycle($race)->create();
        $licence = Licence::factory()->verified()->create(['number' => 'RECALC-1']);
        Enrollment::factory()->confirmed()->recycle($championship)->create(['licence_id' => $licence->getKey()]);
        $participant = $this->participant($race, 'RECALC-1', wildcard: true);
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('races.show', $race))
            ->assertSee(__('Recalculate wildcards'));

        $this->actingAs(User::factory()->racemanager()->create())
            ->post(route('races.wildcards.recalculate', $race))
            ->assertForbidden();

        $this->actingAs($organizer)
            ->post(route('races.wildcards.recalculate', $race))
            ->assertRedirect(route('races.show', $race))
            ->assertSessionHas('flash.banner', __(':changed of :total participants changed wildcard status.', ['changed' => 1, 'total' => 1]));

        $this->assertFalse($participant->fresh()->wildcard);

        $championship->wildcard->enabled = false;
        $championship->save();

        $this->actingAs($organizer)
            ->get(route('races.show', $race))
            ->assertDontSee(__('Recalculate wildcards'));
    }

    public function test_the_enrollment_rule_needs_early_access(): void
    {
        $championship = Championship::factory()->create();
        $organizer = User::factory()->organizer()->create();

        Livewire::actingAs($organizer)
            ->test(WildcardSettings::class, ['championship' => $championship])
            ->assertSee(WildcardStrategy::BASED_ON_ENROLLMENT->localizedName());

        Feature::define(LicencesAndEnrollments::class, fn () => false);

        Livewire::actingAs(User::factory()->organizer()->create())
            ->test(WildcardSettings::class, ['championship' => $championship])
            ->assertDontSee(WildcardStrategy::BASED_ON_ENROLLMENT->localizedName())
            ->set('wildcardForm.enabled', true)
            ->set('wildcardForm.strategy', WildcardStrategy::BASED_ON_ENROLLMENT->value)
            ->call('updateWildcardSettings')
            ->assertHasErrors(['wildcard_strategy']);
    }

    private function championship(): Championship
    {
        return Championship::factory()->create([
            'wildcard' => ['enabled' => true, 'strategy' => WildcardStrategy::BASED_ON_ENROLLMENT],
            'enrollment' => ['enabled' => true, 'requires_competitor_when_underage' => false],
        ]);
    }

    private function participant(Race $race, string $licenceNumber, bool $wildcard = false): Participant
    {
        return Participant::factory()
            ->recycle($race->championship)
            ->recycle($race)
            ->driver(['licence_number' => $licenceNumber])
            ->create(['wildcard' => $wildcard]);
    }
}
