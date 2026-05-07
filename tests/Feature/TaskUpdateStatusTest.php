<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TaskUpdateStatusTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function put_update_with_status_completed_sets_completed_at(): void
    {
        Carbon::setTestNow('2026-05-07 12:00:00');
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create([
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/task/'.$task->id, ['status' => 'completed']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed');

        $this->assertNotNull($response->json('data.completed_at'));

        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);

        Carbon::setTestNow();
    }

    #[Test]
    public function put_update_with_status_in_progress_clears_completed_at(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->completed()->create();

        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/task/'.$task->id, ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.completed_at', null);

        $task->refresh();
        $this->assertSame('in_progress', $task->status);
        $this->assertNull($task->completed_at);
    }
}
