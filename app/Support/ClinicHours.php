<?php

namespace App\Support;

use App\Models\Clinic;

/**
 * Shared translation between the owner/admin single-shift hours editor and the
 * opening_hours rows. The editor exposes one row per weekday (0=Mon..6=Sun);
 * split shifts (a lunch break) collapse to their first window on load.
 */
class ClinicHours
{
    /**
     * @return array<int, array{day_of_week: int, is_closed: bool, opens_at: string, closes_at: string}>
     */
    public static function editorRows(Clinic $clinic): array
    {
        $byDay = $clinic->openingHours->groupBy('day_of_week');

        $rows = [];

        for ($day = 0; $day <= 6; $day++) {
            $dayRows = $byDay->get($day);
            $open = $dayRows?->firstWhere('is_closed', false);

            $rows[] = [
                'day_of_week' => $day,
                'is_closed' => $dayRows !== null && $open === null,
                'opens_at' => $open ? substr((string) $open->opens_at, 0, 5) : '09:00',
                'closes_at' => $open ? substr((string) $open->closes_at, 0, 5) : '18:00',
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $hours
     */
    public static function replace(Clinic $clinic, array $hours): void
    {
        $clinic->openingHours()->delete();

        foreach ($hours as $row) {
            $closed = (bool) ($row['is_closed'] ?? false);

            $clinic->openingHours()->create([
                'day_of_week' => $row['day_of_week'],
                'is_closed' => $closed,
                'opens_at' => $closed ? null : ($row['opens_at'] ?? null),
                'closes_at' => $closed ? null : ($row['closes_at'] ?? null),
            ]);
        }
    }
}
