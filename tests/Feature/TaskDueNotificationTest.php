<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskDueNotificationState;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Testes das rotas /api/notification/task-due.
 *
 * Smoke com tarefa criada via modelo: php artisan test --filter=task_due_notification_smoke_creates_task
 */
class TaskDueNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cria usuário + tarefa com {@see Task::create()} (programada para amanhã às 16:42 UTC)
     * e chama o endpoint de lista. Fluxo enxuto para validar integração rápido.
     */
    #[Test]
    public function task_due_notification_smoke_creates_task_via_model_and_calls_api(): void
    {
        Carbon::setTestNow('2026-12-01 11:11:11');
        config(['app.timezone' => 'UTC']);

        $user = User::factory()->create([
            'name' => 'Usuário teste notificações',
        ]);

        $dueAt = Carbon::parse('2026-12-02 16:42:00', 'UTC');

        $task = Task::create([
            'user_id' => $user->id,
            'name' => 'Comprar insumos QA',
            'description' => 'Criada explicitamente pelo test smoke',
            'status' => 'pending',
            'priority' => 2,
            'original_due_date' => $dueAt,
            'current_due_date' => $dueAt,
            'completed_at' => null,
        ]);

        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/notification/task-due')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unread_count', 1)
            ->assertJsonPath('data.unread.0.task_id', $task->id)
            ->assertJsonPath('data.unread.0.title', 'Comprar insumos QA')
            ->assertJsonPath('data.unread.0.due_summary', 'Vence amanhã');

        Carbon::setTestNow();
    }

    #[Test]
    public function lists_due_within_three_days_partitioned_into_unread_and_read(): void
    {
        Carbon::setTestNow('2026-05-07 14:30:00');
        config(['app.timezone' => 'UTC']);

        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'name' => 'Revisão do relatório',
            'status' => 'pending',
            'completed_at' => null,
            'original_due_date' => Carbon::parse('2026-05-08 10:15:00', 'UTC'),
            'current_due_date' => Carbon::parse('2026-05-08 10:15:00', 'UTC'),
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/notification/task-due');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unread_count', 1)
            ->assertJsonPath('data.unread.0.task_id', $task->id)
            ->assertJsonPath('data.unread.0.title', 'Revisão do relatório')
            ->assertJsonPath('data.unread.0.is_read', false)
            ->assertJsonPath('data.unread.0.due_summary', 'Vence amanhã')
            ->assertJsonPath('data.read', []);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/notification/task-due/'.$task->id.'/read')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.task_id', $task->id);

        $listed = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/notification/task-due');

        $listed->assertOk()
            ->assertJsonPath('data.unread_count', 0)
            ->assertJsonPath('data.unread', [])
            ->assertJsonPath('data.read.0.is_read', true)
            ->assertJsonPath('data.read.0.due_summary', 'Vence amanhã');

        Carbon::setTestNow();
    }

    #[Test]
    public function mark_all_read_and_clear_all_update_visible_list(): void
    {
        Carbon::setTestNow('2026-05-07 09:00:00');
        config(['app.timezone' => 'UTC']);

        $user = User::factory()->create();
        $a = Task::factory()->create([
            'user_id' => $user->id,
            'status' => 'in_progress',
            'completed_at' => null,
            'original_due_date' => Carbon::parse('2026-05-09 08:00:00', 'UTC'),
            'current_due_date' => Carbon::parse('2026-05-09 08:00:00', 'UTC'),
        ]);
        $b = Task::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'completed_at' => null,
            'original_due_date' => Carbon::parse('2026-05-10 20:45:00', 'UTC'),
            'current_due_date' => Carbon::parse('2026-05-10 20:45:00', 'UTC'),
        ]);

        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/notification/task-due/read-all')
            ->assertOk()
            ->assertJsonPath('data.updated', 2);

        $afterRead = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/notification/task-due');
        $afterRead->assertOk()
            ->assertJsonPath('data.unread_count', 0)
            ->assertJsonCount(2, 'data.read');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/notification/task-due/clear')
            ->assertOk()
            ->assertJsonPath('data.updated', 2);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/notification/task-due')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0)
            ->assertJsonPath('data.unread', [])
            ->assertJsonPath('data.read', []);

        $this->assertNotNull(TaskDueNotificationState::query()->where('user_id', $user->id)->where('task_id', $a->id)->value('cleared_at'));
        $this->assertNotNull(TaskDueNotificationState::query()->where('user_id', $user->id)->where('task_id', $b->id)->value('cleared_at'));

        Carbon::setTestNow('2026-05-07 15:00:00'); // avança o relógio para updated_at ficar depois de cleared_at
        $a->update(['name' => 'Nome alterado']);

        $shown = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/notification/task-due');
        $shown->assertOk()
            ->assertJsonPath('data.unread_count', 1)
            ->assertJsonCount(1, 'data.unread')
            ->assertJsonPath('data.unread.0.task_id', $a->id);

        Carbon::setTestNow();
    }

    #[Test]
    public function excludes_completed_far_future_and_foreign_tasks(): void
    {
        Carbon::setTestNow('2026-05-07 14:00:00');
        config(['app.timezone' => 'UTC']);

        $owner = User::factory()->create();
        $peer = User::factory()->create();

        Task::factory()->create([
            'user_id' => $peer->id,
            'completed_at' => null,
            'original_due_date' => Carbon::parse('2026-05-08 12:00:00', 'UTC'),
            'current_due_date' => Carbon::parse('2026-05-08 12:00:00', 'UTC'),
        ]);

        Task::factory()->create([
            'user_id' => $owner->id,
            'status' => 'completed',
            'completed_at' => now(),
            'original_due_date' => Carbon::parse('2026-05-08 12:00:00', 'UTC'),
            'current_due_date' => Carbon::parse('2026-05-08 12:00:00', 'UTC'),
        ]);

        Task::factory()->create([
            'user_id' => $owner->id,
            'status' => 'pending',
            'completed_at' => null,
            'original_due_date' => Carbon::parse('2026-06-01 12:00:00', 'UTC'),
            'current_due_date' => Carbon::parse('2026-06-01 12:00:00', 'UTC'),
        ]);

        $token = JWTAuth::fromUser($owner);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/notification/task-due')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        Carbon::setTestNow();
    }
}
