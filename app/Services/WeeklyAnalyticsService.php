<?php

namespace App\Services;

use App\Models\Task;
use Carbon\Carbon;
use Exception;

class WeeklyAnalyticsService
{
    private const TIME_BLOCKS = [
        ['id' => '06-09', 'label' => '06–09', 'range' => '06:00–09:00', 'from' => 6, 'to' => 8],
        ['id' => '09-12', 'label' => '09–12', 'range' => '09:00–12:00', 'from' => 9, 'to' => 11],
        ['id' => '12-15', 'label' => '12–15', 'range' => '12:00–15:00', 'from' => 12, 'to' => 14],
        ['id' => '15-18', 'label' => '15–18', 'range' => '15:00–18:00', 'from' => 15, 'to' => 17],
        ['id' => '18-21', 'label' => '18–21', 'range' => '18:00–21:00', 'from' => 18, 'to' => 21],
    ];

    private const WEEKDAY_SHORT = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];

    private const WEEKDAY_FULL = [
        'Segunda-feira',
        'Terça-feira',
        'Quarta-feira',
        'Quinta-feira',
        'Sexta-feira',
        'Sábado',
        'Domingo',
    ];

    public function weekly(): array
    {
        $userId = auth('api')->id();

        if (! $userId) {
            throw new Exception('Usuário não autenticado.', 401);
        }

        [$weekStart, $weekEnd] = $this->previousWeekBounds();

        $completions = Task::query()
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$weekStart, $weekEnd])
            ->get(['completed_at']);

        $matrix = array_fill(0, 7, array_fill(0, count(self::TIME_BLOCKS), 0));
        $weekdayTotals = array_fill(0, 7, 0);
        $blockTotals = array_fill(0, count(self::TIME_BLOCKS), 0);

        foreach ($completions as $task) {
            $at = Carbon::parse($task->completed_at);
            $dayIndex = $at->dayOfWeekIso - 1;
            $blockIndex = $this->timeBlockIndex($at->hour);

            if ($blockIndex === null) {
                continue;
            }

            $matrix[$dayIndex][$blockIndex]++;
            $weekdayTotals[$dayIndex]++;
            $blockTotals[$blockIndex]++;
        }

        $heatmap = [];
        foreach ($matrix as $dayIndex => $row) {
            foreach ($row as $timeBlockIndex => $completedCount) {
                $heatmap[] = [
                    'dayIndex' => $dayIndex,
                    'timeBlockIndex' => $timeBlockIndex,
                    'completedCount' => $completedCount,
                ];
            }
        }

        $tasksPerWeekday = [];
        foreach ($weekdayTotals as $dayIndex => $totalCompleted) {
            $tasksPerWeekday[] = [
                'dayIndex' => $dayIndex,
                'shortLabel' => self::WEEKDAY_SHORT[$dayIndex],
                'totalCompleted' => $totalCompleted,
            ];
        }

        $distributionByTimeBlock = [];
        foreach (self::TIME_BLOCKS as $i => $block) {
            $distributionByTimeBlock[] = [
                'blockId' => $block['id'],
                'label' => $block['label'],
                'totalCompleted' => $blockTotals[$i],
            ];
        }

        $totalTasksWeek = array_sum($weekdayTotals);
        $bestDay = $this->pickBestWeekday($weekdayTotals);
        $bestTimeBlock = $this->pickBestTimeBlock($blockTotals);

        return [
            'period' => [
                'start' => $weekStart->toIso8601String(),
                'end' => $weekEnd->toIso8601String(),
                'weekStartsOn' => 'monday',
            ],
            'timeBlocks' => array_column(self::TIME_BLOCKS, 'range'),
            'heatmap' => $heatmap,
            'tasksPerWeekday' => $tasksPerWeekday,
            'distributionByTimeBlock' => $distributionByTimeBlock,
            'summary' => [
                'totalTasksWeek' => $totalTasksWeek,
                'dailyAverage' => $totalTasksWeek > 0 ? round($totalTasksWeek / 7, 1) : 0,
                'bestDay' => $bestDay,
                'bestTimeBlock' => $bestTimeBlock,
                'insights' => $this->buildInsights($totalTasksWeek, $bestDay, $bestTimeBlock),
            ],
            'recordingMeta' => [
                'daysSinceLastCompletion' => $this->daysSinceLastCompletion($userId),
            ],
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function previousWeekBounds(): array
    {
        $anchor = Carbon::now()->subWeek();

        return [
            $anchor->copy()->startOfWeek(Carbon::MONDAY)->startOfDay(),
            $anchor->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay(),
        ];
    }

    private function timeBlockIndex(int $hour): ?int
    {
        foreach (self::TIME_BLOCKS as $i => $block) {
            if ($hour >= $block['from'] && $hour <= $block['to']) {
                return $i;
            }
        }

        return null;
    }

    /** @param  array<int, int>  $weekdayTotals */
    private function pickBestWeekday(array $weekdayTotals): array
    {
        $bestIndex = 0;
        $bestTotal = -1;

        foreach ($weekdayTotals as $i => $total) {
            if ($total > $bestTotal) {
                $bestTotal = $total;
                $bestIndex = $i;
            }
        }

        return [
            'label' => self::WEEKDAY_FULL[$bestIndex],
            'total' => max(0, $bestTotal),
        ];
    }

    /** @param  array<int, int>  $blockTotals */
    private function pickBestTimeBlock(array $blockTotals): array
    {
        $bestIndex = 0;
        $bestTotal = -1;

        foreach ($blockTotals as $i => $total) {
            if ($total > $bestTotal) {
                $bestTotal = $total;
                $bestIndex = $i;
            }
        }

        return [
            'label' => self::TIME_BLOCKS[$bestIndex]['range'],
            'total' => max(0, $bestTotal),
        ];
    }

    private function buildInsights(int $total, array $bestDay, array $bestTimeBlock): array
    {
        if ($total === 0) {
            return [];
        }

        $insights = [];

        if ($bestTimeBlock['total'] > 0) {
            $insights[] = 'A faixa '.$bestTimeBlock['label'].' concentrou o maior volume de conclusões na semana passada.';
        }

        if ($bestDay['total'] > 0) {
            $insights[] = $bestDay['label'].' foi seu dia mais produtivo ('.$bestDay['total'].' concluídas).';
        }

        return $insights;
    }

    private function daysSinceLastCompletion(string $userId): ?int
    {
        $last = Task::query()
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->max('completed_at');

        if (! $last) {
            return null;
        }

        return (int) Carbon::parse($last)->startOfDay()->diffInDays(Carbon::now()->startOfDay());
    }
}
