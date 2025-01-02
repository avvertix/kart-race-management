<?php

declare(strict_types=1);

namespace Tests\Feature\Communications;

use App\Models\CommunicationMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommunicationMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    public static function invalid_communication_requests_provider()
    {
        return [
            [
                [
                    'message' => '',
                    'theme' => '',
                    'target_path' => '',
                    'target_user_role' => '',
                    'starts_at' => '',
                    'ends_at' => '',
                ],
                ['message', 'theme', 'starts_at'],
            ],
            [
                [
                    'message' => 'Message',
                    'theme' => 'hello',
                    'target_path' => '',
                    'target_user_role' => '',
                    'starts_at' => 'not a date',
                    'ends_at' => 'not a date',
                ],
                ['theme', 'starts_at', 'ends_at'],
            ],
            [
                [
                    'message' => Str::random(301),
                    'theme' => 'info',
                    'target_path' => '',
                    'target_user_role' => 'not a role',
                    'starts_at' => now()->addDay()->toDateString(),
                    'ends_at' => '',
                ],
                ['message', 'target_user_role'],
            ],
            [
                [
                    'message' => 'Message',
                    'theme' => 'info',
                    'target_path' => '',
                    'target_user_role' => '',
                    'starts_at' => now()->addDay()->toDateString(),
                    'ends_at' => now()->subDay()->toDateString(),
                ],
                ['ends_at'],
            ],
            [
                [
                    'message' => 'Message',
                    'theme' => 'info',
                    'target_path' => '',
                    'target_user_role' => '',
                    'starts_at' => now()->subDay()->toDateString(),
                    'ends_at' => now()->subDay()->toDateString(),
                ],
                ['starts_at', 'ends_at'],
            ],
            [
                [
                    'message' => 'Message',
                    'theme' => 'info',
                    'target_path' => '',
                    'target_user_role' => ['all'],
                    'starts_at' => now()->subDay()->toDateString(),
                    'ends_at' => null,
                ],
                ['target_user_role.0'],
            ],
        ];
    }

    public function test_communications_page_requires_login()
    {
        $response = $this
            ->get(route('communications.index'));

        $response->assertRedirectToRoute('login');
    }

    public function test_communications_not_accessible_by_tireagents()
    {
        $user = User::factory()->tireagent()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('communications.index'));

        $response->assertForbidden();
    }

    public function test_communications_not_accessible_by_timekeeper()
    {
        $user = User::factory()->timekeeper()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('communications.index'));

        $response->assertForbidden();
    }

    public function test_communications_page_loads()
    {
        $user = User::factory()->admin()->create();

        $communication = CommunicationMessage::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('communications.index'));

        $response->assertOk();

        $response->assertViewHas('communications');

        $actual_communications = $response->viewData('communications');

        $this->assertEquals(1, $actual_communications->count());
        $this->assertTrue($actual_communications->first()->is($communication));

        $response->assertSee($communication->message);
        $response->assertDontSee('No communication messages. Write one!');
        $response->assertSee('Communications');
    }

    public function test_communication_message_can_be_created()
    {
        $user = User::factory()->admin()->create();

        $starts_at = now()->addDays(1)->startOfDay();

        $response = $this
            ->actingAs($user)
            ->from(route('communications.index'))
            ->post(route('communications.store'), [
                'message' => 'This is the message',
                'theme' => 'info',
                'target_path' => '',
                'target_user_role' => '',
                'starts_at' => $starts_at,
                'ends_at' => '',
            ]);

        $response->assertRedirect(route('communications.index'));

        $response->assertSessionHas('status', 'Scheduled message for '.$starts_at->format('d/m/Y'));

        $communication = CommunicationMessage::query()->first();

        $this->assertEquals('This is the message', $communication->message);
        $this->assertEquals('info', $communication->theme);
        $this->assertNull($communication->target_path);
        $this->assertNull($communication->target_user_role);
        $this->assertTrue($communication->starts_at->equalTo($starts_at));
        $this->assertNull($communication->ends_at);
    }

    public function test_communication_message_can_be_created_for_specific_user_roles()
    {
        $user = User::factory()->admin()->create();

        $starts_at = now()->addDays(1)->startOfDay();

        $response = $this
            ->actingAs($user)
            ->from(route('communications.index'))
            ->post(route('communications.store'), [
                'message' => 'This is the message',
                'theme' => 'info',
                'target_path' => '',
                'target_user_role' => ['admin', 'timekeeper'],
                'starts_at' => $starts_at,
                'ends_at' => '',
            ]);

        $response->assertRedirect(route('communications.index'));

        $response->assertSessionHas('status', 'Scheduled message for '.$starts_at->format('d/m/Y'));

        $communication = CommunicationMessage::query()->first();

        $this->assertEquals('This is the message', $communication->message);
        $this->assertEquals('info', $communication->theme);
        $this->assertNull($communication->target_path);
        $this->assertEquals(['admin', 'timekeeper'], $communication->target_user_role->toArray());
        $this->assertTrue($communication->starts_at->equalTo($starts_at));
        $this->assertNull($communication->ends_at);
    }

    public function test_communication_can_be_edited()
    {
        $user = User::factory()->admin()->create();

        $communication = CommunicationMessage::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('communications.edit', $communication));

        $response->assertViewHas('communication', $communication);

        $response->assertSee('Update message');

        $response->assertSee($communication->message);
    }

    public function test_communication_can_be_updated()
    {
        $user = User::factory()->admin()->create();

        $communication = CommunicationMessage::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('communications.index', $communication))
            ->put(route('communications.update', $communication), [
                'message' => 'New message',
                'theme' => 'info',
                'target_path' => '',
                'target_user_role' => '',
                'starts_at' => today(),
                'ends_at' => '',
            ]);

        $response->assertRedirect(route('communications.index'));

        $response->assertSessionHas('status', 'Updated message '.today()->format('d/m/Y'));

        $actual_communication = $communication->fresh();

        $this->assertEquals('New message', $actual_communication->message);
        $this->assertEquals('info', $actual_communication->theme);
        $this->assertNull($actual_communication->target_path);
        $this->assertNull($actual_communication->target_user_role);
        $this->assertEquals(today()->toDateString(), $actual_communication->starts_at->toDateString());
        $this->assertNull($actual_communication->ends_at);
    }

    public function test_communication_can_be_deleted()
    {
        $user = User::factory()->admin()->create();

        $communication = CommunicationMessage::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('communications.index', $communication))
            ->delete(route('communications.destroy', $communication));

        $response->assertRedirect(route('communications.index'));

        $response->assertSessionHas('status', 'Message deleted.');

        $actual_communication = $communication->fresh();

        $this->assertNull($actual_communication);
    }

    /**
     * @dataProvider invalid_communication_requests_provider
     */
    private function test_communication_message_not_created($data, $expectedErrors)
    {
        $user = User::factory()->admin()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('communications.index'))
            ->post(route('communications.store'), $data);

        $response->assertRedirect(route('communications.index'));

        $response->assertSessionHasErrors($expectedErrors);

        $communication = CommunicationMessage::query()->first();

        $this->assertNull($communication);
    }

    /**
     * @dataProvider invalid_communication_requests_provider
     */
    private function test_communication_message_not_updated($data, $expectedErrors)
    {
        $user = User::factory()->admin()->create();

        $communication = CommunicationMessage::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('communications.index'))
            ->put(route('communications.update', $communication), $data);

        $response->assertRedirect(route('communications.index'));

        $response->assertSessionHasErrors($expectedErrors);
    }
}
