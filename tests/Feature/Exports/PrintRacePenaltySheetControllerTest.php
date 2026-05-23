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
}
