<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

/**
 * Utility class for calculating period date ranges based on grouping type.
 */
class PeriodCalculator
{
    /**
     * Calculate periodStart and periodEnd dates based on the group value and groupBy type.
     *
     * @param string $group The group value (e.g., '2025-01-15' for day, '2025-01' for month, '2025' for year)
     * @param string $groupBy The grouping type ('day', 'month', 'year')
     * @return array{0: string, 1: string} Array containing [periodStart, periodEnd]
     */
    public static function calculatePeriodDates(string $group, string $groupBy): array
    {
        return match ($groupBy) {
            'day' => [$group, $group],
            'month' => [
                $group . '-01',
                date('Y-m-t', (int) strtotime($group . '-01')),
            ],
            'year' => [
                $group . '-01-01',
                $group . '-12-31',
            ],
            default => ['', ''],
        };
    }
}
