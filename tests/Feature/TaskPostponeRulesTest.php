<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TaskPostponeRulesTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    /**
     * Intervalo criação → prazo inicial até 7 dias: regras estritas.
     */
    private function makeShortHorizonTask(User $user): Task
    {
        $task = Task::factory()->for($user)->create([
            'status' => 'in_progress',
            'original_due_date' => Carbon::parse('2026-01-08 18:00'),
            'current_due_date' => Carbon::parse('2026-01-08 18:00'),
            'postponed_count' => 0,
            'postponed_date_1' => null,
            'postponed_date_2' => null,
            'postponed_date_3' => null,
        ]);
        $task->forceFill([
            'created_at' => Carbon::parse('2026-01-01 10:00'),
            'updated_at' => Carbon::parse('2026-01-01 10:00'),
        ])->save();

        return $task->fresh();
    }

    /**
     * Exceção 3.5: mais de 7 dias entre criação e prazo inicial.
     */
    private function makeLongHorizonTask(User $user): Task
    {
        $task = Task::factory()->for($user)->create([
            'status' => 'in_progress',
            'original_due_date' => Carbon::parse('2026-01-10 12:00'),
            'current_due_date' => Carbon::parse('2026-01-10 12:00'),
            'postponed_count' => 0,
        ]);
        $task->forceFill([
            'created_at' => Carbon::parse('2026-01-01 09:00'),
            'updated_at' => Carbon::parse('2026-01-01 09:00'),
        ])->save();

        return $task->fresh();
    }

    #[Test]
    public function postpone_strict_rules_false_when_long_horizon_task(): void
    {
        $user = User::factory()->create();
        $task = $this->makeLongHorizonTask($user);
        $this->assertFalse($task->postponeStrictRulesApply());

        $token = $this->tokenFor($user);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/task/'.$task->id);

        $response->assertOk()
            ->assertJsonPath('data.postpone_strict_rules_apply', false);
    }

    #[Test]
    public function first_postponement_rejects_new_due_more_than_four_days_after_planned_completion(): void
    {
        Carbon::setTestNow('2026-01-06 14:00:00');

        $user = User::factory()->create();
        $task = $this->makeShortHorizonTask($user);
        $this->assertTrue($task->postponeStrictRulesApply());

        $token = $this->tokenFor($user);
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/task/'.$task->id.'/postpone', [
                'current_due_date' => '2026-01-14',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment([
                'No 1.º adiamento, o novo prazo não pode ser mais de 4 dias após a conclusão prevista.',
            ]);

        Carbon::setTestNow();
    }

    #[Test]
    public function first_postponement_allows_within_four_days_of_planned_completion(): void
    {
        Carbon::setTestNow('2026-01-06 14:00:00');

        $user = User::factory()->create();
        $task = $this->makeShortHorizonTask($user);

        $token = $this->tokenFor($user);
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/task/'.$task->id.'/postpone', [
                'current_due_date' => '2026-01-12',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.postponed_count', 1)
            ->assertJsonPath('data.status', 'postponed');

        Carbon::setTestNow();
    }

    #[Test]
    public function long_horizon_task_allows_loose_first_postpone_due(): void
    {
        Carbon::setTestNow('2026-01-05 10:00:00');

        $user = User::factory()->create();
        $task = $this->makeLongHorizonTask($user);

        $token = $this->tokenFor($user);
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/task/'.$task->id.'/postpone', [
                'current_due_date' => '2026-06-01',
            ])
            ->assertOk()
            ->assertJsonPath('data.postponed_count', 1);

        Carbon::setTestNow();
    }

    #[Test]
    public function second_postponement_rejected_after_two_day_window_after_first(): void
    {
        Carbon::setTestNow('2026-01-01 09:00:00');

        $user = User::factory()->create();
        $task = $this->makeShortHorizonTask($user);

        $token = $this->tokenFor($user);
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/task/'.$task->id.'/postpone', ['current_due_date' => '2026-01-10'])
            ->assertOk();

        Carbon::setTestNow('2026-01-05 09:00:00');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/task/'.$task->id.'/postpone', ['current_due_date' => '2026-01-15'])
            ->assertStatus(422)
            ->assertJsonFragment([
                'O 2.º adiamento só pode ser feito em até 2 dias após o 1.º adiamento.',
            ]);

        Carbon::setTestNow();
    }

    #[Test]
    public function second_postponement_allowed_within_two_days_after_first(): void
    {
        Carbon::setTestNow('2026-01-01 09:00:00');

        $user = User::factory()->create();
        $task = $this->makeShortHorizonTask($user);

        $token = $this->tokenFor($user);
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/task/'.$task->id.'/postpone', ['current_due_date' => '2026-01-10'])
            ->assertOk();

        Carbon::setTestNow('2026-01-02 09:00:00');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/task/'.$task->id.'/postpone', ['current_due_date' => '2026-01-11'])
            ->assertOk()
            ->assertJsonPath('data.postponed_count', 2);

        Carbon::setTestNow();
    }

    #[Test]
    public function third_postponement_rejected_after_one_day_window_after_second(): void
    {
        Carbon::setTestNow('2026-01-01 09:00:00');

        $user = User::factory()->create();
        $task = $this->makeShortHorizonTask($user);

        $token = $this->tokenFor($user);
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/task/'.$task->id.'/postpone', ['current_due_date' => '2026-01-10'])
            ->assertOk();

        Carbon::setTestNow('2026-01-02 09:00:00');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/task/'.$task->id.'/postpone', ['current_due_date' => '2026-01-11'])
            ->assertOk();

        Carbon::setTestNow('2026-01-05 09:00:00');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/task/'.$task->id.'/postpone', ['current_due_date' => '2026-01-20'])
            ->assertStatus(422)
            ->assertJsonFragment([
                'O 3.º adiamento só pode ser feito em até 1 dia após o 2.º adiamento.',
            ]);

        Carbon::setTestNow();
    }

    #[Test]
    public function third_postponement_allowed_within_one_day_after_second(): void
    {
        Carbon::setTestNow('2026-01-01 09:00:00');

        $user = User::factory()->create();
        $task = $this->makeShortHorizonTask($user);

        $token = $this->tokenFor($user);
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/task/'.$task->id.'/postpone', ['current_due_date' => '2026-01-10'])
            ->assertOk();

        Carbon::setTestNow('2026-01-02 09:00:00');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/task/'.$task->id.'/postpone', ['current_due_date' => '2026-01-11'])
            ->assertOk();

        Carbon::setTestNow('2026-01-03 09:00:00');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/task/'.$task->id.'/postpone', ['current_due_date' => '2026-01-18'])
            ->assertOk()
            ->assertJsonPath('data.postponed_count', 3);

        Carbon::setTestNow();
    }
}
