<?php

declare(strict_types=1);

namespace Tests\Feature\ActivityLog;

use App\Models\ActivityLogEntry;
use App\Models\Participant;
use Illuminate\Support\Facades\DB;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ActivityLogEntryTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_reads_changes_from_attribute_changes_column_for_new_rows(): void
    {
        $participant = Participant::factory()->create();

        $participant->update(['first_name' => 'Updated']);

        $activity = $participant->activitiesAsSubject()->forEvent('updated')->latest('id')->first();

        $this->assertSame('Updated', $activity->changedAttributes()['first_name']);
        $this->assertArrayHasKey('first_name', $activity->changedOldAttributes());
    }

    public function test_reads_changes_from_properties_for_legacy_rows_predating_the_attribute_changes_column(): void
    {
        $participant = Participant::factory()->create();

        $id = DB::table('activity_log')->insertGetId([
            'log_name' => 'default',
            'description' => 'updated',
            'event' => 'updated',
            'subject_type' => $participant->getMorphClass(),
            'subject_id' => $participant->getKey(),
            'attribute_changes' => null,
            'properties' => json_encode([
                'attributes' => ['first_name' => 'Legacy New'],
                'old' => ['first_name' => 'Legacy Old'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activity = ActivityLogEntry::findOrFail($id);

        $this->assertSame(['first_name' => 'Legacy New'], $activity->changedAttributes());
        $this->assertSame(['first_name' => 'Legacy Old'], $activity->changedOldAttributes());
    }
}
