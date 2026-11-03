<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LicenceRequest;
use App\Notifications\LicenceRequestReceived;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class PruneReadNotificationsTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_read_notifications_are_deleted_12_months_after_being_read(): void
    {
        $licenceRequest = LicenceRequest::factory()->submitted()->create();
        $user = $licenceRequest->user;

        $user->notify(new LicenceRequestReceived($licenceRequest));
        $user->notify(new LicenceRequestReceived($licenceRequest));
        $user->notify(new LicenceRequestReceived($licenceRequest));

        [$readLongAgo, $readRecently, $unread] = $user->notifications()->get();
        $readLongAgo->update(['read_at' => now()->subYear()->subDay()]);
        $readRecently->update(['read_at' => now()->subMonths(11)]);

        $this->artisan('notifications:prune-read')->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            [$readRecently->getKey(), $unread->getKey()],
            $user->notifications()->pluck('id')->all(),
        );
    }
}
