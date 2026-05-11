<?php

namespace App\Http\Requests\Task\Concerns;

use Carbon\Carbon;

/**
 * Unifica entrada de prazo máximo (CRUD/adiação) aceitando data + hora opcionais ou datetime completo.
 */
trait NormalizesTaskDueDatetime
{
    /** Data/hora combinados no formato do app para validação Laravel "date". */
    protected function normalizeTaskDueDatetimeField(string $dateKey, ?string $timeKey): void
    {
        $raw = $this->input($dateKey);
        if ($raw === null || $raw === '') {
            return;
        }

        $rawStr = trim((string) $raw);

        if ($this->dateStringLooksLikeFullDatetime($rawStr)) {
            $this->merge([
                $dateKey => Carbon::parse($rawStr)->format('Y-m-d H:i:s'),
            ]);

            return;
        }

        $parsed = static::parseDatePartToCarbon($rawStr);

        $timeRaw = ($timeKey !== null) ? $this->input($timeKey) : null;
        static::applyWallClockFromOptionalTimeField($parsed, $timeRaw);

        $this->merge([
            $dateKey => $parsed->format('Y-m-d H:i:s'),
        ]);
    }

    private static function parseDatePartToCarbon(string $rawStr): Carbon
    {
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $rawStr)) {
            return Carbon::createFromFormat('d/m/Y', $rawStr)->startOfDay();
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawStr)) {
            return Carbon::createFromFormat('Y-m-d', $rawStr)->startOfDay();
        }

        return Carbon::parse($rawStr);
    }

    private static function applyWallClockFromOptionalTimeField(Carbon $parsed, mixed $timeRaw): void
    {
        if ($timeRaw === null || $timeRaw === '') {
            return;
        }

        $t = trim((string) $timeRaw);
        if (! preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $t, $m)) {
            return;
        }

        $parsed->setTime((int) $m[1], (int) $m[2], isset($m[3]) ? (int) $m[3] : 0);
    }

    private function dateStringLooksLikeFullDatetime(string $rawStr): bool
    {
        if (preg_match('/T\d{2}:\d{2}/', $rawStr)) {
            return true;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/', $rawStr)) {
            return true;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}\s+\d{1,2}:\d{2}/', $rawStr)) {
            return true;
        }

        return false;
    }
}
