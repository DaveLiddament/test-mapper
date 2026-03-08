<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Model;

use DaveLiddament\TestMapper\Model\ChangedLineRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChangedLineRange::class)]
final class ChangedLineRangeTest extends TestCase
{
    /**
     * @return iterable<string, array{ChangedLineRange, int, int, bool}>
     */
    public static function overlapDataProvider(): iterable
    {
        // Range starts at line 10, covers 3 lines (10, 11, 12)
        $range = new ChangedLineRange(10, 3);

        yield 'method entirely before range' => [$range, 1, 5, false];
        yield 'method entirely after range' => [$range, 15, 20, false];
        yield 'method overlaps start of range' => [$range, 8, 10, true];
        yield 'method overlaps end of range' => [$range, 12, 15, true];
        yield 'method entirely within range' => [$range, 10, 12, true];
        yield 'method contains entire range' => [$range, 5, 20, true];
        yield 'method touches start exactly' => [$range, 10, 10, true];
        yield 'method touches end exactly' => [$range, 12, 12, true];
        yield 'method ends one before range' => [$range, 1, 9, false];
        yield 'method starts one after range' => [$range, 13, 20, false];

        // Single line range (line 5)
        $singleLine = new ChangedLineRange(5, 1);
        yield 'single line - exact match' => [$singleLine, 5, 5, true];
        yield 'single line - method contains it' => [$singleLine, 3, 8, true];
        yield 'single line - method before' => [$singleLine, 1, 4, false];
        yield 'single line - method after' => [$singleLine, 6, 10, false];

        // Deletion-only (lineCount=0) - never overlaps
        $deletion = new ChangedLineRange(10, 0);
        yield 'deletion - exact position' => [$deletion, 10, 10, false];
        yield 'deletion - method contains position' => [$deletion, 5, 15, false];
    }

    #[Test]
    #[DataProvider('overlapDataProvider')]
    public function itCorrectlyDetectsOverlap(
        ChangedLineRange $range,
        int $methodStart,
        int $methodEnd,
        bool $expectedOverlap,
    ): void {
        self::assertSame($expectedOverlap, $range->overlapsRange($methodStart, $methodEnd));
    }
}
