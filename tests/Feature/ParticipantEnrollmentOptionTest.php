<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Features\LicencesAndEnrollments;
use App\Livewire\ParticipantListing;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\EnrollmentOption;
use App\Models\Licence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\User;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class ParticipantEnrollmentOptionTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    public function test_the_participant_list_shows_the_option_of_each_enrolled_driver_in_every_race(): void
    {
        $championship = Championship::factory()->openForEnrollment()->create();
        $gold = EnrollmentOption::factory()->for($championship)->create(['title' => 'Gold']);
        $silver = EnrollmentOption::factory()->for($championship)->create(['title' => 'Silver']);
        $races = Race::factory()->count(2)->for($championship)->create();

        $goldEnrollment = $this->enrollWith($gold, 'G100001', EnrollmentStatus::Confirmed);
        $elsewhere = EnrollmentOption::factory()->for(Championship::factory()->openForEnrollment())->create(['title' => 'Elsewhere']);
        $this->enrollWith($elsewhere, 'X100001');
        $this->enrollWith($silver, 'S100001', EnrollmentStatus::Withdrawn);
        $organizer = User::factory()->organizer()->create();

        foreach ($races as $race) {
            Participant::factory()->recycle($championship)->for($race)->create(['driver_licence' => hash('sha512', 'G100001')]);
            Participant::factory()->recycle($championship)->for($race)->create(['driver_licence' => hash('sha512', 'S100001')]);
            Participant::factory()->recycle($championship)->for($race)->create(['driver_licence' => hash('sha512', 'X100001')]);

            Livewire::actingAs($organizer)
                ->test(ParticipantListing::class, ['race' => $race])
                ->assertSee('Gold')
                ->assertDontSee('Silver')
                ->assertDontSee('Elsewhere')
                ->assertSee(route('enrollments.show', $goldEnrollment), false);
        }
    }

    public function test_staff_without_access_to_enrollments_see_the_option_without_a_link(): void
    {
        $championship = Championship::factory()->openForEnrollment()->create();
        $gold = EnrollmentOption::factory()->for($championship)->create(['title' => 'Gold']);
        $race = Race::factory()->for($championship)->create();
        $enrollment = $this->enrollWith($gold, 'G100001');
        $participant = Participant::factory()->recycle($championship)->for($race)->category()->create(['driver_licence' => hash('sha512', 'G100001')]);
        $tireAgent = User::factory()->tireagent()->create();

        Livewire::actingAs($tireAgent)
            ->test(ParticipantListing::class, ['race' => $race])
            ->assertSee('Gold')
            ->assertDontSee(route('enrollments.show', $enrollment), false);

        $this->actingAs(User::factory()->racemanager()->create())
            ->get(route('participants.show', $participant))
            ->assertOk()
            ->assertSee('Gold')
            ->assertDontSee(route('enrollments.show', $enrollment), false);

        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('participants.show', $participant))
            ->assertSee(route('enrollments.show', $enrollment), false);

        Feature::for($organizer)->deactivate(LicencesAndEnrollments::class);
        Feature::flushCache();

        $this->actingAs($organizer)
            ->get(route('participants.show', $participant))
            ->assertSee('Gold')
            ->assertDontSee(route('enrollments.show', $enrollment), false);
    }

    private function enrollWith(EnrollmentOption $option, string $licenceNumber, EnrollmentStatus $status = EnrollmentStatus::Submitted): Enrollment
    {
        return Enrollment::factory()->for($option->championship)->create([
            'licence_id' => Licence::factory()->verified()->create(['number' => $licenceNumber]),
            'enrollment_option_id' => $option->getKey(),
            'status' => $status,
        ]);
    }
}
