<?php

namespace App\Support;

use DateTimeInterface;

final class Duration
{
    /**
     * Whole + fractional days between two instants (1 decimal). Null when either end is missing.
     * Uses raw timestamps so the result is identical across Carbon 2 and Carbon 3.
     */
    public static function days(?DateTimeInterface $from, ?DateTimeInterface $to): ?float
    {
        if ($from === null || $to === null) {
            return null;
        }

        return round(($to->getTimestamp() - $from->getTimestamp()) / 86400, 1);
    }

    /** @param iterable<float|null> $values */
    public static function average(iterable $values): ?float
    {
        $clean = [];
        foreach ($values as $v) {
            if ($v !== null) {
                $clean[] = $v;
            }
        }

        return $clean === [] ? null : round(array_sum($clean) / count($clean), 1);
    }
}
