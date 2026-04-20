<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds tasks for BI-style checks on completion and postponements in the **previous calendar week**
 * (Mon 00:00 – Sun 23:59:59 before this week). All dates are derived from now(), so re-running the
 * seeder keeps data aligned with “last week” instead of drifting to fixed past dates.
 */
class TesteUserAnalyticsTaskSeeder extends Seeder
{
    private const USER_EMAIL = 'teste@example.com';

    public function run(): void
    {
        [$weekStart, $weekEnd] = $this->previousCalendarWeekBounds();

        $user = User::query()->where('email', self::USER_EMAIL)->first();
        if ($user === null) {
            $user = User::factory()->create([
                'name' => 'Usuário teste analytics',
                'email' => self::USER_EMAIL,
            ]);
        }

        $this->ensureTaskTypesForUser($user->id);
        $userId = $user->id;

        $total = random_int(50, 80);

        $nCompleted = (int) floor($total * 0.38);
        $nPostponedStatus = (int) floor($total * 0.28);
        $nInProgress = (int) floor($total * 0.14);
        $nPending = (int) floor($total * 0.10);
        $nCanceled = (int) floor($total * 0.05);
        $nSoftDeleted = $total - $nCompleted - $nPostponedStatus - $nInProgress - $nPending - $nCanceled;

        $specs = [];
        foreach (range(1, $nCompleted) as $_) {
            $specs[] = [
                'status' => 'completed',
                'completed_segment' => fake()->boolean(50) ? 'early' : 'late',
                'soft_delete' => false,
            ];
        }
        foreach (range(1, $nPostponedStatus) as $_) {
            $specs[] = ['status' => 'postponed', 'completed_segment' => null, 'soft_delete' => false];
        }
        foreach (range(1, $nInProgress) as $_) {
            $specs[] = ['status' => 'in_progress', 'completed_segment' => null, 'soft_delete' => false];
        }
        foreach (range(1, $nPending) as $_) {
            $specs[] = ['status' => 'pending', 'completed_segment' => null, 'soft_delete' => false];
        }
        foreach (range(1, $nCanceled) as $_) {
            $specs[] = ['status' => 'canceled', 'completed_segment' => null, 'soft_delete' => false];
        }
        foreach (range(1, $nSoftDeleted) as $_) {
            $specs[] = [
                'status' => fake()->randomElement(['pending', 'in_progress', 'canceled']),
                'completed_segment' => null,
                'soft_delete' => true,
            ];
        }

        shuffle($specs);

        $postponeFlags = array_merge(
            array_fill(0, (int) round($total * 0.45), true),
            array_fill(0, $total - (int) round($total * 0.45), false)
        );
        shuffle($postponeFlags);

        foreach ($specs as $index => $spec) {
            $createdAt = $this->randomCarbonBetween($weekStart, $weekEnd->copy()->subDays(1));

            $usePostponementChain = $postponeFlags[$index] ?? false;
            if (($spec['status'] ?? '') === 'postponed') {
                $usePostponementChain = true;
            }

            if ($spec['status'] === 'completed' && ($spec['completed_segment'] ?? '') === 'late') {
                [$attrs, $createdAt] = $this->buildLateCompletedInWeek($createdAt, $usePostponementChain, $weekStart, $weekEnd);
            } else {
                $attrs = $this->buildDateAttributesInWeek($createdAt, $usePostponementChain, $weekStart, $weekEnd);
                if ($spec['status'] === 'completed') {
                    $attrs['completed_at'] = $this->pickCompletedAtInWeek(
                        $createdAt,
                        $attrs['original_due_date'],
                        $weekStart,
                        $weekEnd
                    );
                } else {
                    $attrs['completed_at'] = null;
                }
            }

            $attrs['user_id'] = $userId;
            $attrs['name'] = fake()->sentence(3);
            $attrs['description'] = fake()->optional(0.65)->paragraph();
            $attrs['status'] = $spec['status'];
            $attrs['priority'] = fake()->numberBetween(1, 5);
            $attrs['is_being_viewed'] = false;
            $attrs['last_viewed_at'] = null;
            $attrs['created_at'] = $createdAt;
            $attrs['updated_at'] = $createdAt;

            $task = Task::factory()->create($attrs);

            $this->attachRandomTaskTypes($task, $userId);

            if (! empty($spec['soft_delete'])) {
                $task->delete();
            }
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function previousCalendarWeekBounds(): array
    {
        $thisWeekMonday = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $lastWeekMonday = $thisWeekMonday->copy()->subWeek();
        $lastWeekSundayEnd = $thisWeekMonday->copy()->subSecond();

        return [$lastWeekMonday, $lastWeekSundayEnd];
    }

    private function earliestOf(Carbon $a, Carbon $b): Carbon
    {
        return $a->lessThan($b) ? $a : $b;
    }

    private function latestOf(Carbon $a, Carbon $b): Carbon
    {
        return $a->greaterThan($b) ? $a : $b;
    }

    private function randomCarbonBetween(Carbon $from, Carbon $to): Carbon
    {
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return Carbon::createFromTimestamp(
            fake()->numberBetween($from->getTimestamp(), $to->getTimestamp())
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDateAttributesInWeek(
        Carbon $createdAt,
        bool $usePostponementChain,
        Carbon $weekStart,
        Carbon $weekEnd
    ): array {
        $attrs = [
            'postponed_count' => 0,
            'postponed_date_1' => null,
            'postponed_date_2' => null,
            'postponed_date_3' => null,
        ];

        if (! $usePostponementChain) {
            $latestOriginal = $weekEnd->copy()->subHours(2);
            $earliestOriginal = $createdAt->copy()->addHours(2);
            if ($earliestOriginal->greaterThan($latestOriginal)) {
                $original = $createdAt->copy()->addDay();
            } else {
                $original = $this->randomCarbonBetween($earliestOriginal, $latestOriginal);
            }
            $attrs['original_due_date'] = $original;
            $attrs['current_due_date'] = $original->copy();

            return $attrs;
        }

        $postponedCount = fake()->numberBetween(1, 3);
        $attrs['postponed_count'] = $postponedCount;

        $midWeek = $this->randomCarbonBetween(
            $this->latestOf($createdAt, $weekStart)->copy()->addHours(4),
            $weekEnd->copy()->subDays(1)
        );

        $original = $createdAt->copy()->addHours(fake()->numberBetween(6, 48));
        if ($original->greaterThan($midWeek)) {
            $original = $this->randomCarbonBetween($createdAt->copy()->addHours(2), $midWeek);
        }

        $current = $this->randomCarbonBetween(
            $original->copy()->addHours(2),
            $this->earliestOf($weekEnd, $original->copy()->addDays(4))
        );
        if ($current->lessThanOrEqualTo($original)) {
            $current = $original->copy()->addHours(4);
        }

        $attrs['original_due_date'] = $original;
        $attrs['current_due_date'] = $current;

        $origTs = $original->getTimestamp();
        $curTs = $current->getTimestamp();
        $span = max(1, $curTs - $origTs);

        for ($i = 1; $i <= $postponedCount; $i++) {
            $t = $origTs + (int) ($span * $i / ($postponedCount + 1));
            $attrs['postponed_date_'.$i] = Carbon::createFromTimestamp($t);
        }

        return $attrs;
    }

    /**
     * @return array{0: array<string, mixed>, 1: Carbon}
     */
    private function buildLateCompletedInWeek(
        Carbon $createdAt,
        bool $usePostponementChain,
        Carbon $weekStart,
        Carbon $weekEnd
    ): array {
        $attrs = [
            'postponed_count' => 0,
            'postponed_date_1' => null,
            'postponed_date_2' => null,
            'postponed_date_3' => null,
        ];

        $completedAtMoment = $this->randomCarbonBetween(
            $weekStart->copy()->addDays(2),
            $weekEnd->copy()->subHours(2)
        );

        $currentDue = $completedAtMoment->copy()
            ->subDays(fake()->numberBetween(1, 3))
            ->subHours(fake()->numberBetween(1, 8));

        if ($currentDue->lessThanOrEqualTo($createdAt)) {
            $currentDue = $createdAt->copy()->addHours(12);
        }
        if ($currentDue->greaterThanOrEqualTo($completedAtMoment)) {
            $currentDue = $completedAtMoment->copy()->subHours(3);
        }

        if (! $usePostponementChain) {
            $attrs['original_due_date'] = $currentDue->copy();
            $attrs['current_due_date'] = $currentDue->copy();
        } else {
            $postponedCount = fake()->numberBetween(1, 2);
            $attrs['postponed_count'] = $postponedCount;

            $originalDue = $currentDue->copy()->subDays(fake()->numberBetween(1, 3));
            if ($originalDue->lessThanOrEqualTo($createdAt)) {
                $originalDue = $createdAt->copy()->addHours(4);
            }

            $attrs['original_due_date'] = $originalDue;
            $attrs['current_due_date'] = $currentDue;

            $origTs = $originalDue->getTimestamp();
            $curTs = $currentDue->getTimestamp();
            $span = max(1, $curTs - $origTs);

            for ($i = 1; $i <= $postponedCount; $i++) {
                $t = $origTs + (int) ($span * $i / ($postponedCount + 1));
                $attrs['postponed_date_'.$i] = Carbon::createFromTimestamp($t);
            }
        }

        if ($createdAt->greaterThanOrEqualTo($attrs['original_due_date'])) {
            $createdAt = $attrs['original_due_date']->copy()->subHours(6);
            if ($createdAt->lessThan($weekStart)) {
                $createdAt = $weekStart->copy()->addHours(2);
            }
        }

        $attrs['completed_at'] = $completedAtMoment;

        return [$attrs, $createdAt];
    }

    private function pickCompletedAtInWeek(
        Carbon $createdAt,
        Carbon $originalDue,
        Carbon $weekStart,
        Carbon $weekEnd
    ): Carbon {
        if ($originalDue->lessThanOrEqualTo($createdAt)) {
            $originalDue = $this->randomCarbonBetween(
                $createdAt->copy()->addHours(4),
                $weekEnd->copy()->subHours(4)
            );
        }

        $latest = $this->earliestOf($originalDue->copy()->subHour(), $weekEnd->copy()->subHour());
        $earliest = $this->latestOf($createdAt->copy()->addHour(), $weekStart->copy()->addHour());

        if ($latest->lessThanOrEqualTo($earliest)) {
            return $earliest;
        }

        return $this->randomCarbonBetween($earliest, $latest);
    }

    private function ensureTaskTypesForUser(string $userId): void
    {
        if (TaskType::query()->where('user_id', $userId)->exists()) {
            return;
        }

        $defaults = [
            ['name' => 'Trabalho', 'color' => '#FF5733'],
            ['name' => 'Estudos', 'color' => '#33FF57'],
            ['name' => 'Pessoal', 'color' => '#3357FF'],
        ];

        foreach ($defaults as $type) {
            TaskType::query()->firstOrCreate(
                ['user_id' => $userId, 'name' => $type['name']],
                ['color' => $type['color']]
            );
        }
    }

    private function attachRandomTaskTypes(Task $task, string $userId): void
    {
        $types = TaskType::query()->where('user_id', $userId)->get();
        if ($types->isEmpty()) {
            $this->ensureTaskTypesForUser($userId);
            $types = TaskType::query()->where('user_id', $userId)->get();
        }

        $take = min($types->count(), fake()->numberBetween(1, 3));
        $ids = $types->shuffle()->take($take)->pluck('id')->all();
        if ($ids !== []) {
            $task->taskTypes()->attach($ids);
        }
    }
}
