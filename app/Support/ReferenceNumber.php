<?php

namespace App\Support;

/** Builds the public reference assigned to a paper when it is published, e.g. STD-2026-0042. */
final class ReferenceNumber
{
    public static function format(int $year, int $sequence, string $prefix = 'STD'): string
    {
        return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
    }

    /** @return array{prefix: string, year: int, sequence: int}|null */
    public static function parse(string $reference): ?array
    {
        if (! preg_match('/^([A-Z]+)-(\d{4})-(\d{4,})$/', $reference, $m)) {
            return null;
        }

        return ['prefix' => $m[1], 'year' => (int) $m[2], 'sequence' => (int) $m[3]];
    }
}
