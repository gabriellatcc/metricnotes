<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskDueNotificationState;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TaskDueNotificationService
{
    public const WINDOW_DAYS = 3;

    /**
     * @return array{unread_count: int, unread: array<int, array<string, mixed>>, read: array<int, array<string, mixed>>}
     */
    public function index(User $user): array
    {
        $notifications = $this->visibleNotifications($user);

        $unread = [];
        $read = [];

        foreach ($notifications as $row) {
            if ($row['is_read']) {
                $read[] = $row;
            } else {
                $unread[] = $row;
            }
        }

        return [
            'unread_count' => count($unread),
            'unread' => $unread,
            'read' => $read,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function visibleNotifications(User $user): array
    {
        $tasks = $this->baseDueReminderQuery($user)->orderBy('current_due_date')->get();

        if ($tasks->isEmpty()) {
            return [];
        }

        $states = TaskDueNotificationState::query()
            ->where('user_id', $user->id)
            ->whereIn('task_id', $tasks->pluck('id')->all())
            ->get()
            ->keyBy('task_id');

        $items = [];

        foreach ($tasks as $task) {
            $state = $states->get($task->id);

            if ($this->isHiddenByClear($task, $state)) {
                continue;
            }

            $tz = config('app.timezone');
            $todayStart = now($tz)->startOfDay();
            $dueDayStart = $task->current_due_date->copy()->timezone($tz)->startOfDay();
            $calendarDaysUntilDue = (int) $todayStart->diffInDays($dueDayStart, false);

            if ($calendarDaysUntilDue < 0 || $calendarDaysUntilDue > self::WINDOW_DAYS) {
                continue;
            }

            $dueLocal = $task->current_due_date->copy()->timezone($tz);
            $dueSummary = $this->dueSummaryPortuguese($calendarDaysUntilDue, $dueLocal);
            $isRead = $state !== null && $state->read_at !== null;

            $items[] = [
                'task_id' => $task->id,
                'title' => $task->name,
                'due_summary' => $dueSummary,
                'is_read' => $isRead,
                'read_at' => $state?->read_at?->toIso8601String(),
                'current_due_date' => $dueLocal->toIso8601String(),
                'days_until_due' => $calendarDaysUntilDue,
            ];
        }

        usort($items, function (array $a, array $b) {
            if ($a['days_until_due'] === $b['days_until_due']) {
                return strcmp((string) $a['task_id'], (string) $b['task_id']);
            }

            return $a['days_until_due'] <=> $b['days_until_due'];
        });

        return $items;
    }

    protected function baseDueReminderQuery(User $user)
    {
        $tz = config('app.timezone');
        $start = Carbon::parse(now($tz)->toDateString().' 00:00:00', $tz);
        $end = $start->copy()->addDays(self::WINDOW_DAYS)->endOfDay();

        return Task::query()
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->whereNotIn('status', ['completed', 'canceled'])
            ->whereNotNull('current_due_date')
            ->where('current_due_date', '>=', $start)
            ->where('current_due_date', '<=', $end);
    }

    protected function dueSummaryPortuguese(int $calendarDaysUntilDue, Carbon $dueLocal): string
    {
        $time = $dueLocal->format('H:i');

        return match ($calendarDaysUntilDue) {
            0 => 'Vence hoje às '.$time,
            1 => 'Vence amanhã às '.$time,
            2 => 'Vence em dois dias às '.$time,
            3 => 'Vence em três dias às '.$time,
            default => 'Vence em '.$calendarDaysUntilDue.' dias às '.$time,
        };
    }

    public function markRead(User $user, string $taskId): void
    {
        /** @var Task|null $task */
        $task = Task::query()->where('user_id', $user->id)->whereKey($taskId)->first();

        if (! $task) {
            abort(404, 'Tarefa não encontrada.');
        }

        if (! $this->markableAsReminder($user, $task)) {
            abort(422, 'Esta tarefa não aparece nos lembretes de vencimento.');
        }

        TaskDueNotificationState::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'task_id' => $task->id,
            ],
            ['read_at' => now()],
        );
    }

    public function markAllRead(User $user): int
    {
        $indexed = Collection::make($this->visibleNotifications($user))->keyBy('task_id');

        foreach ($indexed as $taskId => $_row) {
            TaskDueNotificationState::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'task_id' => $taskId,
                ],
                ['read_at' => now()],
            );
        }

        return $indexed->count();
    }

    public function clearAll(User $user): int
    {
        $now = now();
        $indexed = Collection::make($this->visibleNotifications($user))->keyBy('task_id');

        foreach ($indexed as $taskId => $_row) {
            TaskDueNotificationState::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'task_id' => $taskId,
                ],
                [
                    'cleared_at' => $now,
                    'read_at' => null,
                ],
            );
        }

        return $indexed->count();
    }

    protected function markableAsReminder(User $user, Task $task): bool
    {
        if (
            $task->completed_at !== null
            || in_array($task->status, ['completed', 'canceled'], true)
            || $task->current_due_date === null
        ) {
            return false;
        }

        $existsInBase = $this->baseDueReminderQuery($user)
            ->whereKey($task->id)
            ->exists();

        if (! $existsInBase) {
            return false;
        }

        $state = TaskDueNotificationState::query()
            ->where('user_id', $user->id)
            ->where('task_id', $task->id)
            ->first();

        if ($this->isHiddenByClear($task, $state)) {
            return false;
        }

        $tz = config('app.timezone');
        $todayStart = now($tz)->startOfDay();
        $calendarDaysUntilDue = (int) $todayStart->diffInDays(
            $task->current_due_date->copy()->timezone($tz)->startOfDay(),
            false,
        );

        return $calendarDaysUntilDue >= 0 && $calendarDaysUntilDue <= self::WINDOW_DAYS;
    }

    protected function isHiddenByClear(Task $task, ?TaskDueNotificationState $state): bool
    {
        if (! $state || $state->cleared_at === null) {
            return false;
        }

        return $task->updated_at->lessThanOrEqualTo($state->cleared_at);
    }
}
