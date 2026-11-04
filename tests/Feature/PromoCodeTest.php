<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\PromoCodes\IssuePromoCodes;
use App\Enums\EntitlementMode;
use App\Enums\PromoCodeStatus;
use App\Features\LicencesAndProducts;
use App\Models\Championship;
use App\Models\PromoCode;
use App\Models\User;
use Laravel\Pennant\Feature;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class PromoCodeTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_the_organizer_issues_random_codes(): void
    {
        $championship = $this->championship();
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('championships.promo-codes.create', $championship))
            ->assertSuccessful();

        $this->actingAs($organizer)
            ->post(route('championships.promo-codes.store', $championship), [
                'mode' => EntitlementMode::Credit->value,
                'amount' => '2',
                'count' => '5',
            ])
            ->assertRedirect(route('championships.promo-codes.index', $championship));

        $codes = $championship->promoCodes()->get();

        $this->assertCount(5, $codes);
        $this->assertCount(5, $codes->pluck('code')->unique());

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{8}$/', $code->code);
            $this->assertSame(PromoCodeStatus::Unclaimed, $code->status);
            $this->assertSame(EntitlementMode::Credit, $code->mode);
            $this->assertSame(2, $code->amount);
            $this->assertTrue($code->createdBy->is($organizer));
        }

        $this->actingAs($organizer)
            ->get(route('championships.promo-codes.index', $championship))
            ->assertSee($codes->first()->code)
            ->assertSee(PromoCodeStatus::Unclaimed->label());
    }

    public function test_a_custom_code_is_case_insensitive_and_unique_across_the_platform(): void
    {
        $championship = $this->championship();
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->post(route('championships.promo-codes.store', $championship), [
                'mode' => EntitlementMode::Balance->value,
                'amount' => '50.00',
                'count' => '1',
                'code' => 'winter-26',
            ])
            ->assertSessionHasNoErrors();

        $code = PromoCode::query()->sole();

        $this->assertSame('WINTER-26', $code->code);
        $this->assertSame(5000, $code->amount);
        $this->assertTrue(PromoCode::findByCode(' Winter-26 ')->is($code));

        $this->actingAs($organizer)
            ->post(route('championships.promo-codes.store', $this->championship()), [
                'mode' => EntitlementMode::Credit->value,
                'amount' => '1',
                'count' => '1',
                'code' => 'WINTER-26',
            ])
            ->assertSessionHasErrors('code');

        $this->actingAs($organizer)
            ->post(route('championships.promo-codes.store', $championship), [
                'mode' => EntitlementMode::Credit->value,
                'amount' => '1',
                'count' => '3',
                'code' => 'SPRING-26',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_the_public_page_shows_the_code_without_personal_data(): void
    {
        $championship = $this->championship(['title' => 'Promo championship']);
        $code = app(IssuePromoCodes::class)($championship, User::factory()->organizer()->create(), EntitlementMode::Credit, 2)->sole();

        $this->get(route('public.promo-codes.show', $code))
            ->assertSuccessful()
            ->assertSee($code->code)
            ->assertSee('Promo championship')
            ->assertSee(trans_choice(':count race fee|:count race fees', 2, ['count' => 2]))
            ->assertSee('<svg', false);

        $claimer = User::factory()->create(['name' => 'Claiming Person', 'email' => 'claimer@example.com']);
        $claimed = PromoCode::factory()->claimed()->recycle($championship)->create(['claimed_by_user_id' => $claimer->getKey()]);

        $this->get(route('public.promo-codes.show', $claimed))
            ->assertSuccessful()
            ->assertSee(PromoCodeStatus::Claimed->label())
            ->assertDontSee('Claiming Person')
            ->assertDontSee('claimer@example.com');
    }

    public function test_random_codes_avoid_ambiguous_characters(): void
    {
        foreach (range(1, 500) as $attempt) {
            $this->assertDoesNotMatchRegularExpression('/[0O1I]/', PromoCode::randomCode());
        }
    }

    public function test_credits_are_whole_race_fees(): void
    {
        $this->actingAs(User::factory()->organizer()->create())
            ->post(route('championships.promo-codes.store', $this->championship()), [
                'mode' => EntitlementMode::Credit->value,
                'amount' => '1.5',
                'count' => '1',
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_a_revoked_code_says_so(): void
    {
        $championship = $this->championship();
        $organizer = User::factory()->organizer()->create();
        $code = PromoCode::factory()->recycle($championship)->create();
        $claimed = PromoCode::factory()->claimed()->recycle($championship)->create();

        $this->actingAs($organizer)
            ->post(route('promo-codes.revoke', $code))
            ->assertRedirect(route('championships.promo-codes.index', $championship));

        $this->assertSame(PromoCodeStatus::Revoked, $code->fresh()->status);

        $this->get(route('public.promo-codes.show', $code))
            ->assertSee(__('This code was revoked and can no longer be claimed.'));

        $this->actingAs($organizer)
            ->post(route('promo-codes.revoke', $claimed))
            ->assertForbidden();
    }

    public function test_codes_need_entitlements_and_an_organizer(): void
    {
        $championship = Championship::factory()->create();

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('championships.promo-codes.index', $championship))
            ->assertNotFound();

        $this->actingAs(User::factory()->create())
            ->get(route('championships.promo-codes.index', $this->championship()))
            ->assertForbidden();

        Feature::define(LicencesAndProducts::class, fn () => false);

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('championships.promo-codes.index', $this->championship()))
            ->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function championship(array $attributes = []): Championship
    {
        return Championship::factory()->create(['bonus_enabled' => false, 'entitlements_enabled' => true, ...$attributes]);
    }
}
