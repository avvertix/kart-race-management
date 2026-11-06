<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\DeleteParticipant;
use App\Actions\LicenceRequests\EmitLicence;
use App\Actions\PromoCodes\ClaimPromoCode;
use App\Enums\EntitlementMode;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\PromoCodeStatus;
use App\Models\Category;
use App\Models\Championship;
use App\Models\Entitlement;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Participant;
use App\Models\PromoCode;
use App\Models\Race;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\CreateCompetitor;
use Tests\CreateDriver;
use Tests\CreateMechanic;
use Tests\CreateVehicle;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class EntitlementTest extends TestCase
{
    use CreateCompetitor;
    use CreateDriver;
    use CreateMechanic;
    use CreateVehicle;
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    public function test_a_user_claims_a_code_for_a_licence_valid_in_the_championship_year(): void
    {
        $championship = $this->championship();
        $code = PromoCode::factory()->recycle($championship)->create(['amount' => 2]);
        $user = User::factory()->create();
        $licence = Licence::factory()->for($user)->create(['number' => 'CLAIM-1', 'validity_year' => $championship->start_at->year]);
        $oldLicence = Licence::factory()->for($user)->create(['number' => 'OLD-1', 'validity_year' => $championship->start_at->year - 1]);

        $this->actingAs($user)
            ->get(route('public.promo-codes.show', $code))
            ->assertSee('CLAIM-1')
            ->assertDontSee('OLD-1');

        $this->actingAs($user)
            ->post(route('promo-codes.claim', $code), ['licence' => $oldLicence->ulid])
            ->assertSessionHasErrors('licence');

        $this->actingAs($user)
            ->post(route('promo-codes.claim', $code), ['licence' => $licence->ulid])
            ->assertRedirect(route('public.promo-codes.show', $code));

        $code = $code->fresh();
        $entitlement = Entitlement::query()->sole();

        $this->assertSame(PromoCodeStatus::Claimed, $code->status);
        $this->assertSame($licence->getKey(), $code->licence_id);
        $this->assertSame($licence->getKey(), $entitlement->licence_id);
        $this->assertSame($licence->number_hash, $entitlement->licence_hash);
        $this->assertSame(EntitlementMode::Credit, $entitlement->mode);
        $this->assertSame(2, $entitlement->remaining);
        $this->assertTrue($entitlement->source->is($code));

        $this->actingAs(User::factory()->create())
            ->post(route('promo-codes.claim', $code), ['licence' => Licence::factory()->create()->ulid])
            ->assertSessionHasErrors('promo_code');

        $this->assertSame(1, Entitlement::query()->count());
    }

    public function test_a_code_is_claimed_only_once(): void
    {
        $championship = $this->championship();
        $code = PromoCode::factory()->recycle($championship)->create();

        app(ClaimPromoCode::class)($code, Licence::hashNumber('FIRST'));

        $this->expectException(ValidationException::class);

        try {
            app(ClaimPromoCode::class)($code, Licence::hashNumber('SECOND'));
        } finally {
            $this->assertSame(1, Entitlement::query()->count());
            $this->assertSame(Licence::hashNumber('FIRST'), $code->fresh()->licence_hash);
        }
    }

    public function test_bonuses_never_apply_with_entitlements(): void
    {
        $race = Race::factory()->recycle($this->championship())->create(['bonus_enabled' => true]);

        $this->assertFalse($race->isBonusEnabled());
        $this->assertTrue($race->isEntitlementEnabled());
    }

    public function test_a_typed_code_leads_to_its_page(): void
    {
        $code = PromoCode::factory()->recycle($this->championship())->create(['code' => 'TYPED-26']);

        $this->actingAs(User::factory()->create())
            ->post(route('promo-codes.find'), ['code' => ' typed-26 '])
            ->assertRedirect(route('public.promo-codes.show', $code));

        $this->actingAs(User::factory()->create())
            ->post(route('promo-codes.find'), ['code' => 'NOPE'])
            ->assertSessionHasErrors('code');
    }

    public function test_a_guest_code_typed_at_registration_keeps_working_with_the_same_licence_number(): void
    {
        Notification::fake();

        $championship = $this->championship();
        $code = PromoCode::factory()->recycle($championship)->create(['code' => 'GUEST-26', 'amount' => 2]);
        [$first, $second] = Race::factory()->count(2)->recycle($championship)->create();
        $category = Category::factory()->recycle($championship)->create(['registration_price' => 15000]);

        $this->register($first, $category, ['promo_code' => 'guest-26'])->assertSessionHasNoErrors();

        $participant = Participant::query()->where('race_id', $first->getKey())->sole();

        $this->assertSame(PromoCodeStatus::Claimed, $code->fresh()->status);
        $this->assertSame(Licence::hashNumber('D0001'), $code->fresh()->licence_hash);
        $this->assertSame(-15000, $participant->cost->discount);
        $this->assertSame(1, Entitlement::query()->sole()->remaining);

        $this->register($second, $category)->assertSessionHasNoErrors();

        $this->assertSame(-15000, Participant::query()->where('race_id', $second->getKey())->sole()->cost->discount);
        $this->assertSame(0, Entitlement::query()->sole()->remaining);
    }

    public function test_an_invalid_code_refuses_the_registration(): void
    {
        Notification::fake();

        $championship = $this->championship();
        $race = Race::factory()->recycle($championship)->create();
        $optedOut = Race::factory()->recycle($championship)->create(['entitlements_enabled' => false]);
        $category = Category::factory()->recycle($championship)->create();
        PromoCode::factory()->recycle($championship)->create(['code' => 'REVOKED-1', 'status' => PromoCodeStatus::Revoked]);
        PromoCode::factory()->claimed()->recycle($championship)->create(['code' => 'CLAIMED-1']);
        PromoCode::factory()->create(['code' => 'OTHER-CHAMP']);
        PromoCode::factory()->recycle($championship)->create(['code' => 'VALID-1']);

        foreach (['MISSING-1', 'REVOKED-1', 'CLAIMED-1', 'OTHER-CHAMP'] as $typed) {
            $this->register($race, $category, ['promo_code' => $typed])->assertSessionHasErrors('promo_code');
        }

        $this->register($optedOut, $category, ['promo_code' => 'VALID-1'])->assertSessionHasErrors('promo_code');

        $this->assertSame(0, Participant::query()->count());
        $this->assertSame(PromoCodeStatus::Unclaimed, PromoCode::findByCode('VALID-1')->status);
    }

    public function test_credits_are_used_before_balance_and_a_balance_covers_what_is_left(): void
    {
        Notification::fake();

        $championship = $this->championship();
        $category = Category::factory()->recycle($championship)->create(['registration_price' => 15000]);
        [$first, $second, $third] = Race::factory()->count(3)->recycle($championship)->create();
        $hash = Licence::hashNumber('D0001');

        $balance = Entitlement::factory()->recycle($championship)->create(['licence_hash' => $hash, 'mode' => EntitlementMode::Balance, 'amount' => 20000, 'remaining' => 20000, 'created_at' => now()->subDays(2)]);
        $credit = Entitlement::factory()->recycle($championship)->create(['licence_hash' => $hash, 'mode' => EntitlementMode::Credit, 'amount' => 1, 'remaining' => 1]);

        $this->register($first, $category)->assertSessionHasNoErrors();
        $this->register($second, $category)->assertSessionHasNoErrors();
        $this->register($third, $category)->assertSessionHasNoErrors();

        $this->assertSame(0, $credit->fresh()->remaining);
        $this->assertSame(0, $balance->fresh()->remaining);
        $this->assertSame([-15000, -15000, -5000], Participant::query()->orderBy('id')->get()->map(fn (Participant $participant) => $participant->cost->discount)->all());
    }

    public function test_deleting_or_cancelling_gives_the_usage_back(): void
    {
        Notification::fake();

        $championship = $this->championship();
        $category = Category::factory()->recycle($championship)->create(['registration_price' => 15000]);
        [$first, $second] = Race::factory()->count(2)->recycle($championship)->create();
        $entitlement = Entitlement::factory()->recycle($championship)->create(['licence_hash' => Licence::hashNumber('D0001'), 'amount' => 2, 'remaining' => 2]);

        $this->register($first, $category)->assertSessionHasNoErrors();
        $this->register($second, $category)->assertSessionHasNoErrors();
        $this->assertSame(0, $entitlement->fresh()->remaining);

        (new DeleteParticipant)(Participant::query()->where('race_id', $first->getKey())->sole());
        $this->assertSame(1, $entitlement->fresh()->remaining);

        $this->actingAs(User::factory()->organizer()->create())
            ->delete(route('races.destroy', $second));
        $this->assertSame(2, $entitlement->fresh()->remaining);
        $this->assertSame(0, Participant::query()->where('race_id', $second->getKey())->sole()->cost->discount);
    }

    public function test_a_code_claimed_by_number_moves_to_the_emitted_licence_and_shows_its_usage(): void
    {
        Notification::fake();

        $championship = $this->championship();
        $code = PromoCode::factory()->recycle($championship)->create(['amount' => 3]);
        $licenceRequest = LicenceRequest::factory()->verified()->create(['payment_status' => LicenceRequestPaymentStatus::Paid]);
        $race = Race::factory()->recycle($championship)->create();
        $category = Category::factory()->recycle($championship)->create();

        $this->register($race, $category, ['promo_code' => $code->code, 'driver_licence_number' => 'A123456'])->assertSessionHasNoErrors();

        $licence = app(EmitLicence::class)($licenceRequest, User::factory()->organizer()->create(), 'A123456', $championship->start_at->year, UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'));

        $this->assertSame($licence->getKey(), Entitlement::query()->sole()->licence_id);
        $this->assertSame($licence->getKey(), $code->fresh()->licence_id);

        $this->get(route('public.promo-codes.show', $code))
            ->assertSee(__(':used of :amount used', ['used' => 1, 'amount' => trans_choice(':count race fee|:count race fees', 3, ['count' => 3])]));
    }

    private function championship(): Championship
    {
        return Championship::factory()->create(['bonus_enabled' => false, 'entitlements_enabled' => true]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function register(Race $race, Category $category, array $input = []): \Illuminate\Testing\TestResponse
    {
        $this->travelTo($race->registration_closes_at->subHour());

        $response = $this->post(route('races.registration.store', $race), [
            'bib' => 100,
            'category' => $category->ulid,
            ...$this->generateValidDriver(),
            ...$this->generateValidCompetitor(),
            ...$this->generateValidMechanic(),
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
            ...$input,
        ]);

        $this->travelBack();

        return $response;
    }
}
