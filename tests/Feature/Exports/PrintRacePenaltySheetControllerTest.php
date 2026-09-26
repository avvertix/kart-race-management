<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Exports\PrintRacePenaltySheet;
use App\Models\Category;
use App\Models\Participant;
use App\Models\Race;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Testing\TestResponseAssert as PHPUnit;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class PrintRacePenaltySheetControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_print_requires_authentication(): void
    {
        $race = Race::factory()->create();

        $response = $this->get(route('races.penalty-sheet.print', $race));

        $response->assertRedirect(route('login'));
    }

    public function test_print_forbidden_for_tireagent(): void
    {
        $user = User::factory()->tireagent()->create();
        $race = Race::factory()->create();

        $response = $this->actingAs($user)->get(route('races.penalty-sheet.print', $race));

        $response->assertForbidden();
    }

    public function test_print_returns_a_pdf(): void
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create([
            'event_start_at' => Carbon::parse('2024-06-15'),
            'title' => 'Race title',
        ]);

        Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->confirmed()
            ->create(['race_id' => $race->getKey()]);

        $response = $this->actingAs($user)->get(route('races.penalty-sheet.print', $race));

        $expected_filename = 'penalty-sheet-2024-06-15-race-title.pdf';

        $this->assertTrue(str($response->getContent())->substr(0, 4)->is('%PDF'));

        $contentDisposition = explode(';', $response->headers->get('content-disposition', ''));

        if (isset($contentDisposition[1]) &&
            mb_trim(explode('=', $contentDisposition[1])[0]) !== 'filename') {
            PHPUnit::withResponse($response)->fail(
                'Unsupported Content-Disposition header provided.'.PHP_EOL.
                'Disposition ['.mb_trim(explode('=', $contentDisposition[1])[0]).'] found in header, [filename] expected.'
            );
        }

        $message = "Expected file [{$expected_filename}] is not present in Content-Disposition header.";

        if (! isset($contentDisposition[1])) {
            PHPUnit::withResponse($response)->fail($message);
        } else {
            PHPUnit::withResponse($response)->assertSame(
                $expected_filename,
                isset(explode('=', $contentDisposition[1])[1])
                    ? mb_trim(explode('=', $contentDisposition[1])[1], " \"'")
                    : '',
                $message
            );
        }
    }

    public function test_default_groups_one_per_category(): void
    {
        $race = Race::factory()->create();

        $categoryA = Category::factory()->recycle($race->championship)->create(['name' => 'Mini Junior']);
        $categoryB = Category::factory()->recycle($race->championship)->create(['name' => 'Senior']);

        Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryA->getKey(),
        ]);
        Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryB->getKey(),
        ]);

        $export = new PrintRacePenaltySheet($race, []);

        $groups = (fn () => $this->buildGroups())->call($export);

        $this->assertCount(2, $groups);
        $this->assertSame('Mini Junior', $groups[0]['title']);
        $this->assertSame('Senior', $groups[1]['title']);
        $this->assertCount(1, $groups[0]['participants']);
        $this->assertCount(1, $groups[1]['participants']);
    }

    public function test_default_groups_excludes_categories_without_confirmed_participants(): void
    {
        $race = Race::factory()->create();

        $categoryA = Category::factory()->recycle($race->championship)->create(['name' => 'Mini Junior']);
        $categoryB = Category::factory()->recycle($race->championship)->create(['name' => 'Senior']);

        Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryA->getKey(),
        ]);

        Participant::factory()->recycle($race->championship)->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryB->getKey(),
        ]);

        $export = new PrintRacePenaltySheet($race, []);

        $groups = (fn () => $this->buildGroups())->call($export);

        $this->assertCount(1, $groups);
        $this->assertSame('Mini Junior', $groups[0]['title']);
    }

    public function test_custom_groups_combine_categories(): void
    {
        $race = Race::factory()->create();

        $categoryA = Category::factory()->recycle($race->championship)->create(['name' => 'Mini Junior']);
        $categoryB = Category::factory()->recycle($race->championship)->create(['name' => 'Mini Senior']);

        Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryA->getKey(),
        ]);
        Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryB->getKey(),
        ]);

        $export = new PrintRacePenaltySheet($race, [[$categoryA->ulid, $categoryB->ulid]]);

        $groups = (fn () => $this->buildGroups())->call($export);

        $this->assertCount(1, $groups);
        $this->assertStringContainsString('Mini Junior', $groups[0]['title']);
        $this->assertStringContainsString('Mini Senior', $groups[0]['title']);
        $this->assertCount(2, $groups[0]['participants']);
        $this->assertTrue($groups[0]['showCategory']);
    }

    public function test_wildcards_separated_when_requested_and_enabled(): void
    {
        $race = Race::factory()->create();
        $race->championship->wildcard->enabled = true;
        $race->championship->save();

        $categoryA = Category::factory()->recycle($race->championship)->create(['name' => 'Mini Junior']);
        $categoryB = Category::factory()->recycle($race->championship)->create(['name' => 'Senior']);

        Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryA->getKey(),
            'wildcard' => false,
        ]);
        Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryA->getKey(),
            'wildcard' => true,
        ]);
        Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryB->getKey(),
            'wildcard' => false,
        ]);

        $export = new PrintRacePenaltySheet($race, [], true);

        $groups = (fn () => $this->buildGroups())->call($export);

        $this->assertCount(3, $groups);
        $this->assertSame('Mini Junior', $groups[0]['title']);
        $this->assertSame('Mini Junior - Wildcard', $groups[1]['title']);
        $this->assertSame('Senior', $groups[2]['title']);
        $this->assertFalse($groups[0]['participants']->first()->wildcard);
        $this->assertTrue($groups[1]['participants']->first()->wildcard);
        $this->assertCount(1, $groups[2]['participants']);
    }

    public function test_wildcard_categories_can_be_grouped_freely(): void
    {
        $race = Race::factory()->create();
        $race->championship->wildcard->enabled = true;
        $race->championship->save();

        $categoryA = Category::factory()->recycle($race->championship)->create(['name' => 'Mini Junior']);
        $categoryB = Category::factory()->recycle($race->championship)->create(['name' => 'Mini Senior']);

        $regularA = Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryA->getKey(),
            'wildcard' => false,
            'bib' => 10,
        ]);
        $wildcardA = Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryA->getKey(),
            'wildcard' => true,
            'bib' => 11,
        ]);
        $regularB = Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryB->getKey(),
            'wildcard' => false,
            'bib' => 20,
        ]);
        $wildcardB = Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryB->getKey(),
            'wildcard' => true,
            'bib' => 21,
        ]);

        $export = new PrintRacePenaltySheet($race, [
            [$categoryA->ulid, $categoryB->ulid],
            [$categoryA->ulid.PrintRacePenaltySheet::WILDCARD_SUFFIX, $categoryB->ulid.PrintRacePenaltySheet::WILDCARD_SUFFIX],
        ], true);

        $groups = (fn () => $this->buildGroups())->call($export);

        $this->assertCount(2, $groups);
        $this->assertSame('Mini Junior / Mini Senior', $groups[0]['title']);
        $this->assertSame([$regularA->getKey(), $regularB->getKey()], $groups[0]['participants']->map->getKey()->all());
        $this->assertSame('Mini Junior - Wildcard / Mini Senior - Wildcard', $groups[1]['title']);
        $this->assertSame([$wildcardA->getKey(), $wildcardB->getKey()], $groups[1]['participants']->map->getKey()->all());
        $this->assertTrue($groups[1]['showCategory']);
    }

    public function test_wildcard_category_can_be_grouped_with_its_regular_category(): void
    {
        $race = Race::factory()->create();
        $race->championship->wildcard->enabled = true;
        $race->championship->save();

        $category = Category::factory()->recycle($race->championship)->create(['name' => 'Mini Junior']);

        Participant::factory()->recycle($race->championship)->confirmed()->count(2)->sequence(
            ['wildcard' => false],
            ['wildcard' => true],
        )->create([
            'race_id' => $race->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $export = new PrintRacePenaltySheet($race, [
            [$category->ulid, $category->ulid.PrintRacePenaltySheet::WILDCARD_SUFFIX],
        ], true);

        $groups = (fn () => $this->buildGroups())->call($export);

        $this->assertCount(1, $groups);
        $this->assertSame('Mini Junior / Mini Junior - Wildcard', $groups[0]['title']);
        $this->assertCount(2, $groups[0]['participants']);
        $this->assertFalse($groups[0]['participants'][0]->wildcard);
        $this->assertTrue($groups[0]['participants'][1]->wildcard);
    }

    public function test_wildcard_keys_ignored_when_not_separating_wildcards(): void
    {
        $race = Race::factory()->create();
        $race->championship->wildcard->enabled = true;
        $race->championship->save();

        $category = Category::factory()->recycle($race->championship)->create(['name' => 'Mini Junior']);

        Participant::factory()->recycle($race->championship)->confirmed()->count(2)->sequence(
            ['wildcard' => false],
            ['wildcard' => true],
        )->create([
            'race_id' => $race->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $export = new PrintRacePenaltySheet($race, [
            [$category->ulid],
            [$category->ulid.PrintRacePenaltySheet::WILDCARD_SUFFIX],
        ]);

        $groups = (fn () => $this->buildGroups())->call($export);

        $this->assertCount(1, $groups);
        $this->assertSame('Mini Junior', $groups[0]['title']);
        $this->assertCount(2, $groups[0]['participants']);
    }

    public function test_print_with_separate_wildcards_returns_a_pdf(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();
        $race->championship->wildcard->enabled = true;
        $race->championship->save();

        $category = Category::factory()->recycle($race->championship)->create();

        Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $category->getKey(),
            'wildcard' => true,
        ]);

        $response = $this->actingAs($user)->get(route('races.penalty-sheet.print', [
            'race' => $race,
            'separate_wildcards' => 1,
            'groups' => [[$category->ulid.PrintRacePenaltySheet::WILDCARD_SUFFIX]],
        ]));

        $response->assertOk();
        $this->assertTrue(str($response->getContent())->substr(0, 4)->is('%PDF'));
    }

    public function test_wildcards_not_separated_when_championship_has_wildcard_disabled(): void
    {
        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create(['name' => 'Mini Junior']);

        Participant::factory()->recycle($race->championship)->confirmed()->count(2)->sequence(
            ['wildcard' => false],
            ['wildcard' => true],
        )->create([
            'race_id' => $race->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $export = new PrintRacePenaltySheet($race, [], true);

        $groups = (fn () => $this->buildGroups())->call($export);

        $this->assertCount(1, $groups);
        $this->assertCount(2, $groups[0]['participants']);
    }

    public function test_wildcards_not_separated_when_not_requested(): void
    {
        $race = Race::factory()->create();
        $race->championship->wildcard->enabled = true;
        $race->championship->save();

        $category = Category::factory()->recycle($race->championship)->create(['name' => 'Mini Junior']);

        Participant::factory()->recycle($race->championship)->confirmed()->count(2)->sequence(
            ['wildcard' => false],
            ['wildcard' => true],
        )->create([
            'race_id' => $race->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $export = new PrintRacePenaltySheet($race, []);

        $groups = (fn () => $this->buildGroups())->call($export);

        $this->assertCount(1, $groups);
        $this->assertCount(2, $groups[0]['participants']);
    }

    public function test_wildcard_participants_are_marked_in_print(): void
    {
        $race = Race::factory()->create();
        $race->championship->wildcard->enabled = true;
        $race->championship->save();

        $category = Category::factory()->recycle($race->championship)->create(['name' => 'Mini Junior']);

        Participant::factory()->recycle($race->championship)->confirmed()->count(2)->sequence(
            ['wildcard' => false],
            ['wildcard' => true],
        )->create([
            'race_id' => $race->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $export = new PrintRacePenaltySheet($race, []);

        $html = view('prints.penalty-sheet', (fn () => $this->viewData())->call($export))->render();

        $this->assertSame(1, mb_substr_count($html, '<span class="wildcard-marker">W</span>'));
    }

    public function test_wildcard_participants_are_not_marked_when_championship_has_wildcard_disabled(): void
    {
        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create(['name' => 'Mini Junior']);

        Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $category->getKey(),
            'wildcard' => true,
        ]);

        $export = new PrintRacePenaltySheet($race, []);

        $html = view('prints.penalty-sheet', (fn () => $this->viewData())->call($export))->render();

        $this->assertStringNotContainsString('<span class="wildcard-marker">W</span>', $html);
    }
}
