<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ChampionshipBannerControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_get_banner_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.banner.index', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_uploading_banner_requires_login(): void
    {
        Storage::fake('championship-banners');

        $championship = Championship::factory()->create();

        $file = UploadedFile::fake()->image('banner.jpg', 200, 200);

        $response = $this
            ->from(route('championships.edit', $championship))
            ->post(route('championships.banner.store', $championship), [
                'banner' => $file,
            ]);

        $response->assertRedirect(route('login'));
    }

    public function test_championship_banner_uploaded()
    {
        Storage::fake('championship-banners');

        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $file = UploadedFile::fake()->image('banner.jpg', 200, 200);

        $response = $this
            ->actingAs($user)
            ->from(route('championships.edit', $championship))
            ->post(route('championships.banner.store', $championship), [
                'banner' => $file,
            ]);

        $response->assertRedirect(route('championships.edit', $championship));

        $response->assertSessionHas('flash.banner', 'Banner uploaded.');

        $updatedChampionship = $championship->fresh();

        $this->assertNotNull($updatedChampionship->banner_path);

        Storage::disk('championship-banners')->assertExists($updatedChampionship->banner_path);
    }

    public function test_championship_banner_replaced()
    {
        Storage::fake('championship-banners');

        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create([
            'banner_path' => 'old-banner.jpg',
        ]);

        $file = UploadedFile::fake()->image('banner.jpg', 200, 200);

        Storage::disk('championship-banners')->putFileAs('', $file, 'old-banner.jpg');

        $response = $this
            ->actingAs($user)
            ->from(route('championships.edit', $championship))
            ->post(route('championships.banner.store', $championship), [
                'banner' => $file,
            ]);

        $response->assertRedirect(route('championships.edit', $championship));

        $response->assertSessionHas('flash.banner', 'Banner uploaded.');

        $updatedChampionship = $championship->fresh();

        $this->assertNotNull($updatedChampionship->banner_path);

        Storage::disk('championship-banners')->assertExists($updatedChampionship->banner_path);
        Storage::disk('championship-banners')->assertMissing('old-banner.jpg');
    }

    public function test_championship_banner_must_be_an_image()
    {
        Storage::fake('championship-banners');

        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $file = UploadedFile::fake()->create('banner.pdf', 10, 'application/pdf');

        $response = $this
            ->actingAs($user)
            ->from(route('championships.edit', $championship))
            ->post(route('championships.banner.store', $championship), [
                'banner' => $file,
            ]);

        $response->assertRedirect(route('championships.edit', $championship));

        $response->assertSessionHasErrors('banner');

        $updatedChampionship = $championship->fresh();

        $this->assertNull($updatedChampionship->banner_path);
    }

    public function test_banner_downloadable()
    {
        Storage::fake('championship-banners');

        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create([
            'banner_path' => 'banner.jpg',
        ]);

        $file = UploadedFile::fake()->image('banner.jpg', 200, 200);

        Storage::disk('championship-banners')->putFileAs('', $file, 'banner.jpg');

        $response = $this
            ->actingAs($user)
            ->get(route('championships.banner.index', $championship));

        $response->assertSuccessful();

        $response->assertHeader('content-type', 'image/jpeg');

        Storage::disk('championship-banners')->assertExists($championship->banner_path);
    }
}
