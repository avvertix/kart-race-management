<?php

namespace Tests\Feature\Communications;

use App\Models\Communication;
use App\Models\CommunicationMessage;
use App\Models\User;
use App\View\Components\BroadcastCommunications;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastCommunicationsComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_render_active_messages()
    {
        $saved_communication = CommunicationMessage::factory()
            ->create([
                'message' => 'Test **communication** with [link](http://localhost)',
                'starts_at' => today()->startOfDay()->toDateString(),
                'ends_at' => today()->addDays(1)->endOfDay()->toDateString()
            ]);

        $view = $this->component(BroadcastCommunications::class);

        $view->assertSee('<p>Test <strong>communication</strong> with <a href="http://localhost">link</a></p>', false);
    }

    public function test_pages_include_communications_component()
    {
        $user = User::factory()->admin()->create();

        $saved_communication = CommunicationMessage::factory()
            ->create([
                'message' => 'Test **communication** with [link](http://localhost)',
                'starts_at' => today()->startOfDay()->toDateString(),
                'ends_at' => today()->addDays(1)->endOfDay()->toDateString()
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('communications.index'));

        $response->assertOk();

        $response->assertSeeHtml('<p>Test <strong>communication</strong> with <a href="http://localhost">link</a></p>');
    }

    public function test_communications_are_bound_to_user_role()
    {
        $user = User::factory()->organizer()->create();

        $prosumer_communication = CommunicationMessage::factory()
            ->create([
                'message' => 'Organizer\'s message',
                'starts_at' => today()->startOfDay()->toDateString(),
                'ends_at' => today()->addDays(1)->endOfDay()->toDateString(),
                'target_user_role' => ['organizer'],
            ]);
        
        $admin_communication = CommunicationMessage::factory()
            ->create([
                'message' => 'Only for admin',
                'starts_at' => today()->startOfDay()->toDateString(),
                'ends_at' => today()->addDays(1)->endOfDay()->toDateString(),
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();

        $response->assertSeeHtml('<p>Organizer\'s message</p>');
        $response->assertDontSeeHtml('<p>Only for admin</p>');
    }
}
