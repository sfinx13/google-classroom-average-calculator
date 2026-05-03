<?php

declare(strict_types=1);

namespace App\Tests\Utils;

use App\Utils\TrimesterHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TrimesterHelperTest extends TestCase
{
    #[DataProvider('provideDates')]
    public function testGetTrimester(string $startDate, ?string $expectedTrimester): void
    {
        $this->assertSame($expectedTrimester, TrimesterHelper::getTrimester($startDate));
    }

    public static function provideDates(): \Generator
    {
        yield 'T1 start' => ['2025-10-01', '1'];
        yield 'T1 middle' => ['2025-11-15', '1'];
        yield 'T1 end' => ['2025-12-31', '1'];
        yield 'T2 start' => ['2026-01-01', '2'];
        yield 'T2 middle' => ['2026-02-15', '2'];
        yield 'T2 end' => ['2026-04-30', '2'];
        yield 'T3 start' => ['2026-05-01', '3'];
        yield 'T3 middle' => ['2026-06-15', '3'];
        yield 'T3 end' => ['2026-07-31', '3'];
        yield 'Before T1' => ['2025-09-30', null];
        yield 'After T3' => ['2026-08-01', null];
        yield 'Invalid date format' => ['not-a-date', null];
    }
}
