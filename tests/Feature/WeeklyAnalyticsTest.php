<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class WeeklyAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function weekly_requires_authentication(): void
    {
        $this->getJson('/api/analytics/weekly')
            ->assertStatus(401);
    }

    #[Test]
    public function weekly_aggregates_previous_week_completions(): void
    {
        Carbon::setTestNow('2026-06-02 10:00:00');

        $user = User::factory()->create();
        $other = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        Task::factory()->for($user)->create([
            'status' => 'completed',
            'completed_at' => '2026-05-27 10:30:00',
        ]);
        Task::factory()->for($user)->create([
            'status' => 'completed',
            'completed_at' => '2026-05-27 11:00:00',
        ]);
        Task::factory()->for($user)->create([
            'status' => 'completed',
            'completed_at' => '2026-05-28 14:00:00',
        ]);
        Task::factory()->for($other)->create([
            'status' => 'completed',
            'completed_at' => '2026-05-27 10:00:00',
        ]);
        Task::factory()->for($user)->create([
            'status' => 'completed',
            'completed_at' => '2026-06-02 09:00:00',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/analytics/weekly');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.totalTasksWeek', 3)
            ->assertJsonPath('data.summary.bestDay.label', 'Quarta-feira')
            ->assertJsonPath('data.summary.bestDay.total', 2)
            ->assertJsonPath('data.summary.bestTimeBlock.label', '09:00–12:00')
            ->assertJsonPath('data.summary.bestTimeBlock.total', 2)
            ->assertJsonPath('data.recordingMeta.daysSinceLastCompletion', 0)
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'timeBlocks',
                    'heatmap',
                    'tasksPerWeekday',
                    'distributionByTimeBlock',
                    'summary' => ['insights'],
                    'recordingMeta',
                ],
            ]);

        Carbon::setTestNow();
    }
}
