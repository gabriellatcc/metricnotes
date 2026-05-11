<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-6 months', 'now');

        $originalDue = (clone $createdAt)->modify('+'.fake()->numberBetween(3, 45).' days');
        $currentDue = (clone $originalDue)->modify('+'.fake()->numberBetween(0, 14).' days');

        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->optional(0.7)->paragraph(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed', 'postponed', 'canceled']),
            'priority' => fake()->numberBetween(1, 5),
            'original_due_date' => $originalDue,
            'current_due_date' => $currentDue,
            'postponed_count' => 0,
            'postponed_date_1' => null,
            'postponed_date_2' => null,
            'postponed_date_3' => null,
            'is_being_viewed' => false,
            'last_viewed_at' => null,
            'total_view_time_seconds' => 0,
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $created = $attributes['created_at'] ?? fake()->dateTimeBetween('-6 months', 'now');
            if ($created instanceof \DateTimeInterface) {
                $completedAfter = Carbon::parse($created)->addHours(fake()->numberBetween(2, 72));
            } else {
                $completedAfter = now()->subDays(fake()->numberBetween(1, 30));
            }

            return [
                'status' => 'completed',
                'completed_at' => $completedAfter,
            ];
        });
    }
}
