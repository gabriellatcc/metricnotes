<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TaskDueDatetimeNormalizationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function store_combines_ddmmyyyy_with_due_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-10 12:00:00', config('app.timezone')));

        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/task', [
                'name' => 'Prazo com hora',
                'due_date' => '15/05/2026',
                'due_time' => '17:45',
                'priority' => 2,
            ]);

        $res->assertOk()->assertJsonPath('success', true);

        $task = Task::query()->where('name', 'Prazo com hora')->first();
        $this->assertNotNull($task);
        $this->assertSame(
            '2026-05-15 17:45:00',
            $task->current_due_date->timezone(config('app.timezone'))->format('Y-m-d H:i:s')
        );

        Carbon::setTestNow();
    }

    #[Test]
    public function store_date_only_keeps_midnight_for_legacy_payloads(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-10 12:00:00', config('app.timezone')));

        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/task', [
                'name' => 'Só data',
                'due_date' => '20/05/2026',
                'priority' => 2,
            ])
            ->assertOk();

        $task = Task::query()->where('name', 'Só data')->first();
        $this->assertNotNull($task);
        $this->assertSame(
            '2026-05-20 00:00:00',
            $task->current_due_date->timezone(config('app.timezone'))->format('Y-m-d H:i:s')
        );

        Carbon::setTestNow();
    }

    #[Test]
    public function update_merges_current_due_date_and_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-10 12:00:00', config('app.timezone')));

        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create([
            'status' => 'in_progress',
            'original_due_date' => Carbon::parse('2026-05-12 00:00:00'),
            'current_due_date' => Carbon::parse('2026-05-12 00:00:00'),
        ]);

        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/task/'.$task->id, [
                'current_due_date' => '18/05/2026',
                'current_due_time' => '9:30',
            ])
            ->assertOk();

        $task->refresh();
        $this->assertSame(
            '2026-05-18 09:30:00',
            $task->current_due_date->timezone(config('app.timezone'))->format('Y-m-d H:i:s')
        );

        Carbon::setTestNow();
    }
}
