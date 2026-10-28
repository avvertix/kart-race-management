<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LicenceDocumentKind;
use App\Enums\LicenceDocumentScope;
use App\Enums\LicenceFormTemplate;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use App\Models\DriverLicence;
use App\Models\LicenceGrade;
use App\Models\LicenceType;
use App\Models\LicenceTypeDocument;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class LicenceTypeControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_drivers_cannot_access_licence_types(): void
    {
        $user = User::factory()->create();

        $licenceType = LicenceType::factory()->create();

        $this->actingAs($user)->get(route('licence-types.index'))->assertForbidden();
        $this->actingAs($user)->get(route('licence-types.create'))->assertForbidden();
        $this->actingAs($user)->post(route('licence-types.store'), ['provider' => 'aci', 'role' => 'driver', 'name' => 'Driver licence'])->assertForbidden();
        $this->actingAs($user)->get(route('licence-types.edit', $licenceType))->assertForbidden();
        $this->actingAs($user)->put(route('licence-types.update', $licenceType), ['name' => 'Changed'])->assertForbidden();

        $this->assertEquals(1, LicenceType::count());
        $this->assertNotEquals('Changed', $licenceType->fresh()->name);
    }

    public function test_organizer_sees_licence_types_and_their_forms(): void
    {
        $user = User::factory()->organizer()->create();

        $licenceType = LicenceType::factory()
            ->has(LicenceGrade::factory()->state(['name' => 'C Junior']), 'grades')
            ->has(LicenceTypeDocument::factory()->signedForm(), 'documents')
            ->create(['name' => 'Driver licence']);

        LicenceType::factory()->inactive()->create(['name' => 'Old mechanic licence']);

        $this->actingAs($user)
            ->get(route('licence-types.index'))
            ->assertSuccessful()
            ->assertSee('Driver licence')
            ->assertSee('Old mechanic licence');

        $this->actingAs($user)
            ->get(route('licence-types.create'))
            ->assertSuccessful();

        $this->actingAs($user)
            ->get(route('licence-types.edit', $licenceType))
            ->assertSuccessful()
            ->assertSee('C Junior')
            ->assertSee('Self-declaration');
    }

    public function test_organizer_creates_aci_driver_type_with_grades_per_level(): void
    {
        $user = User::factory()->organizer()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('licence-types.store'), [
                'provider' => 'aci',
                'role' => 'driver',
                'name' => 'Driver licence',
                'description' => 'Karting driver licence',
                'active' => '1',
                'grades' => [
                    ['level' => 'national', 'name' => 'C Junior'],
                    ['level' => 'national', 'name' => 'C Senior'],
                    ['level' => 'international', 'name' => 'International B'],
                ],
                'documents' => [
                    ['name' => 'Identity card', 'kind' => 'upload', 'scope' => 'each_parent', 'underage_only' => '1', 'required' => '1'],
                    ['name' => 'Self-declaration', 'kind' => 'signed_form', 'template' => 'self_declaration', 'scope' => 'holder', 'required' => '1'],
                ],
            ]);

        $response->assertRedirectToRoute('licence-types.index');
        $response->assertSessionHasNoErrors();

        $licenceType = LicenceType::sole();

        $this->assertEquals(LicenceProvider::Aci, $licenceType->provider);
        $this->assertEquals(LicenceRole::Driver, $licenceType->role);
        $this->assertEquals('Driver licence', $licenceType->name);
        $this->assertNull($licenceType->race_licence_category);
        $this->assertTrue($licenceType->active);

        $this->assertEquals([
            ['national', 'C Junior', 0],
            ['national', 'C Senior', 1],
            ['international', 'International B', 2],
        ], $licenceType->grades->map(fn ($grade) => [$grade->level->value, $grade->name, $grade->sort])->all());

        [$identityCard, $selfDeclaration] = $licenceType->documents;

        $this->assertEquals(LicenceDocumentKind::Upload, $identityCard->kind);
        $this->assertEquals(LicenceDocumentScope::EachParent, $identityCard->scope);
        $this->assertNull($identityCard->template);
        $this->assertTrue($identityCard->underage_only);

        $this->assertEquals(LicenceDocumentKind::SignedForm, $selfDeclaration->kind);
        $this->assertEquals(LicenceFormTemplate::SelfDeclaration, $selfDeclaration->template);
        $this->assertFalse($selfDeclaration->underage_only);
        $this->assertEquals(1, $selfDeclaration->sort);
    }

    public function test_organizer_creates_asi_type_without_levels(): void
    {
        $user = User::factory()->organizer()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('licence-types.store'), [
                'provider' => 'asi',
                'role' => 'driver',
                'name' => 'ASI karting licence',
                'race_licence_category' => DriverLicence::LOCAL_NATIONAL->value,
                'grades' => [
                    ['name' => 'Agonistica'],
                ],
            ]);

        $response->assertRedirectToRoute('licence-types.index');
        $response->assertSessionHasNoErrors();

        $licenceType = LicenceType::sole();

        $this->assertEquals(LicenceProvider::Asi, $licenceType->provider);
        $this->assertEquals(DriverLicence::LOCAL_NATIONAL, $licenceType->race_licence_category);
        $this->assertFalse($licenceType->active);
        $this->assertNull($licenceType->grades->sole()->level);
        $this->assertEquals('Agonistica', $licenceType->grades->sole()->name);
    }

    public function test_aci_grades_require_a_level(): void
    {
        $user = User::factory()->organizer()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('licence-types.store'), [
                'provider' => 'aci',
                'role' => 'driver',
                'name' => 'Driver licence',
                'grades' => [
                    ['name' => 'C Junior'],
                ],
            ]);

        $response->assertSessionHasErrors('grades.0.level');
        $this->assertDatabaseEmpty('licence_types');
    }

    public function test_asi_grades_cannot_have_a_level(): void
    {
        $user = User::factory()->organizer()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('licence-types.store'), [
                'provider' => 'asi',
                'role' => 'driver',
                'name' => 'ASI karting licence',
                'race_licence_category' => DriverLicence::LOCAL_NATIONAL->value,
                'grades' => [
                    ['level' => 'national', 'name' => 'Agonistica'],
                ],
            ]);

        $response->assertSessionHasErrors('grades.0.level');
        $this->assertDatabaseEmpty('licence_types');
    }

    public function test_race_licence_category_is_required_for_asi_drivers_only(): void
    {
        $user = User::factory()->organizer()->create();

        $this
            ->actingAs($user)
            ->post(route('licence-types.store'), [
                'provider' => 'asi',
                'role' => 'driver',
                'name' => 'ASI karting licence',
            ])
            ->assertSessionHasErrors('race_licence_category');

        $this
            ->actingAs($user)
            ->post(route('licence-types.store'), [
                'provider' => 'aci',
                'role' => 'driver',
                'name' => 'Driver licence',
                'race_licence_category' => DriverLicence::LOCAL_NATIONAL->value,
            ])
            ->assertSessionHasErrors('race_licence_category');

        $this->assertDatabaseEmpty('licence_types');
    }

    public function test_updating_syncs_grades_and_documents(): void
    {
        $user = User::factory()->organizer()->create();

        $licenceType = LicenceType::factory()->create();
        $kept = LicenceGrade::factory()->for($licenceType)->create(['name' => 'C Junior', 'sort' => 0]);
        $removed = LicenceGrade::factory()->for($licenceType)->create(['name' => 'D', 'sort' => 1]);
        $document = LicenceTypeDocument::factory()->for($licenceType)->create(['name' => 'Identity card']);

        $response = $this
            ->actingAs($user)
            ->put(route('licence-types.update', $licenceType), [
                'provider' => 'asi',
                'name' => 'Driver licence',
                'active' => '1',
                'grades' => [
                    ['level' => 'international', 'name' => 'International B'],
                    ['id' => $kept->getKey(), 'level' => 'national', 'name' => 'C Junior (under 14)'],
                ],
                'documents' => [
                    ['id' => $document->getKey(), 'name' => 'Identity document', 'kind' => 'upload', 'scope' => 'holder', 'required' => '0'],
                ],
            ]);

        $response->assertRedirectToRoute('licence-types.index');
        $response->assertSessionHasNoErrors();

        $licenceType->refresh();

        $this->assertEquals(LicenceProvider::Aci, $licenceType->provider);

        $this->assertEquals([
            ['International B', 0],
            ['C Junior (under 14)', 1],
        ], $licenceType->grades->map(fn ($grade) => [$grade->name, $grade->sort])->all());

        $this->assertEquals($kept->getKey(), $licenceType->grades->last()->getKey());
        $this->assertModelMissing($removed);

        $this->assertEquals('Identity document', $licenceType->documents->sole()->name);
        $this->assertFalse($licenceType->documents->sole()->required);
    }

    public function test_grades_of_another_type_cannot_be_changed(): void
    {
        $user = User::factory()->organizer()->create();

        $licenceType = LicenceType::factory()->create();
        $otherGrade = LicenceGrade::factory()->create(['name' => 'C Senior']);

        $response = $this
            ->actingAs($user)
            ->put(route('licence-types.update', $licenceType), [
                'name' => 'Driver licence',
                'grades' => [
                    ['id' => $otherGrade->getKey(), 'level' => 'national', 'name' => 'Changed'],
                ],
            ]);

        $response->assertSessionHasErrors('grades.0.id');
        $this->assertEquals('C Senior', $otherGrade->fresh()->name);
    }

    public function test_deactivated_types_are_not_offered_but_stay_available(): void
    {
        $user = User::factory()->organizer()->create();

        $licenceType = LicenceType::factory()->create();

        $this
            ->actingAs($user)
            ->put(route('licence-types.update', $licenceType), [
                'name' => $licenceType->name,
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($licenceType->fresh()->active);
        $this->assertEmpty(LicenceType::active()->get());
        $this->assertTrue(LicenceType::whereKey($licenceType->getKey())->exists());
    }

    public function test_signed_forms_require_a_template(): void
    {
        $user = User::factory()->organizer()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('licence-types.store'), [
                'provider' => 'aci',
                'role' => 'mechanic',
                'name' => 'Mechanic licence',
                'documents' => [
                    ['name' => 'Privacy', 'kind' => 'signed_form', 'scope' => 'holder'],
                ],
            ]);

        $response->assertSessionHasErrors('documents.0.template');
        $this->assertDatabaseEmpty('licence_types');
    }
}
