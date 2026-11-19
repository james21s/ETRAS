<?php

namespace Tests\Unit;

use App\Support\Duration;
use App\Support\FileSize;
use App\Support\ReferenceNumber;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function test_reference_number_is_zero_padded(): void
    {
        $this->assertSame('STD-2026-0042', ReferenceNumber::format(2026, 42));
        $this->assertSame('ABC-2025-1234', ReferenceNumber::format(2025, 1234, 'ABC'));
    }

    public function test_reference_number_round_trips(): void
    {
        $this->assertSame(
            ['prefix' => 'STD', 'year' => 2026, 'sequence' => 7],
            ReferenceNumber::parse(ReferenceNumber::format(2026, 7))
        );
        $this->assertNull(ReferenceNumber::parse('not-a-reference'));
    }

    public function test_file_size_formatting(): void
    {
        $this->assertSame('0 B', FileSize::format(0));
        $this->assertSame('512 B', FileSize::format(512));
        $this->assertSame('1.5 KB', FileSize::format(1536));
        $this->assertSame('1.0 MB', FileSize::format(1048576));
        $this->assertSame('2.5 GB', FileSize::format((int) (2.5 * 1024 ** 3)));
    }

    public function test_duration_in_days(): void
    {
        $from = new DateTimeImmutable('2026-01-01 00:00:00');
        $to = new DateTimeImmutable('2026-01-11 12:00:00');

        $this->assertSame(10.5, Duration::days($from, $to));
        $this->assertNull(Duration::days(null, $to));
        $this->assertNull(Duration::days($from, null));
    }

    public function test_duration_average_ignores_nulls(): void
    {
        $this->assertSame(15.0, Duration::average([10.0, 20.0, null]));
        $this->assertNull(Duration::average([null, null]));
        $this->assertNull(Duration::average([]));
    }
}
