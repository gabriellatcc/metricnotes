<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskViewSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TaskViewSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_sets_being_viewed_and_returns_session(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create([
            'is_being_viewed' => false,
            'total_view_time_seconds' => 0,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/task/'.$task->id.'/view/session/start');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session_id', fn ($id) => is_string($id) && strlen($id) > 0);

        $task->refresh();
        $this->assertTrue($task->is_being_viewed);
        $this->assertSame(1, TaskViewSession::query()->where('task_id', $task->id)->count());
    }

    public function test_end_accumulates_duration_from_server_clock(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create([
            'total_view_time_seconds' => 0,
        ]);
        $token = JWTAuth::fromUser($user);

        $start = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/task/'.$task->id.'/view/session/start');
        $sessionId = $start->json('data.session_id');

        $session = TaskViewSession::query()->findOrFail($sessionId);
        $session->forceFill([
            'started_at' => now()->subSeconds(225),
        ])->save();

        $end = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/task/'.$task->id.'/view/session/end', [
                'session_id' => $sessionId,
            ]);

        $end->assertOk()
            ->assertJsonPath('data.duration_seconds', 225);

        $task->refresh();
        $this->assertSame(225, $task->total_view_time_seconds);
        $this->assertFalse($task->is_being_viewed);
    }

    public function test_start_is_idempotent_for_open_session(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();
        $token = JWTAuth::fromUser($user);

        $a = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/task/'.$task->id.'/view/session/start');
        $b = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/task/'.$task->id.'/view/session/start');

        $this->assertSame($a->json('data.session_id'), $b->json('data.session_id'));
        $this->assertSame(1, TaskViewSession::query()->where('task_id', $task->id)->count());
    }

    public function test_cannot_end_same_session_twice(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();
        $token = JWTAuth::fromUser($user);

        $start = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/task/'.$task->id.'/view/session/start');
        $sessionId = $start->json('data.session_id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/task/'.$task->id.'/view/session/end', ['session_id' => $sessionId])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/task/'.$task->id.'/view/session/end', ['session_id' => $sessionId])
            ->assertStatus(422);
    }

    public function test_forbidden_for_other_users_task(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $task = Task::factory()->for($owner)->create();
        $token = JWTAuth::fromUser($other);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/task/'.$task->id.'/view/session/start')
            ->assertForbidden();
    }

    public function test_stale_open_session_is_closed_on_next_start(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create(['total_view_time_seconds' => 0]);
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/task/'.$task->id.'/view/session/start')
            ->assertOk();

        $stale = TaskViewSession::query()
            ->where('task_id', $task->id)
            ->whereNull('ended_at')
            ->sole();
        $stale->forceFill([
            'started_at' => now()->subSeconds(14_401),
        ])->save();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/task/'.$task->id.'/view/session/start')
            ->assertOk();

        $task->refresh();
        $this->assertSame(14_401, $task->total_view_time_seconds);
        $this->assertSame(1, TaskViewSession::query()->whereNotNull('ended_at')->count());
        $this->assertSame(1, TaskViewSession::query()->whereNull('ended_at')->count());
    }
}
