<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Model;

use DaveLiddament\TestMapper\Model\ChangedFile;
use DaveLiddament\TestMapper\Model\ChangedLineRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChangedFile::class)]
final class ChangedFileTest extends TestCase
{
    #[Test]
    public function itDetectsOverlapWithAnyRange(): void
    {
        $file = new ChangedFile('src/Foo.php', [
            new ChangedLineRange(5, 3),   // lines 5-7
            new ChangedLineRange(20, 2),  // lines 20-21
        ]);

        self::assertTrue($file->overlapsRange(6, 8));   // overlaps first range
        self::assertTrue($file->overlapsRange(19, 20)); // overlaps second range
        self::assertFalse($file->overlapsRange(10, 15)); // between ranges
    }

    #[Test]
    public function itReturnsFalseWithNoRanges(): void
    {
        $file = new ChangedFile('src/Foo.php', []);

        self::assertFalse($file->overlapsRange(1, 100));
    }
}
