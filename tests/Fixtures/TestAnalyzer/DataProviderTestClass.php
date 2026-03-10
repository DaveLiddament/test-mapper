<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DataProviderTestClass extends TestCase
{
    #[Test]
    #[DataProvider('additionProvider')]
    public function itAdds(int $a, int $b, int $expected): void
    {
        self::assertSame($expected, $a + $b);
    }

    #[Test]
    public function itHasNoProvider(): void
    {
        self::assertTrue(true);
    }

    /**
     * @return iterable<array{int, int, int}>
     */
    public static function additionProvider(): iterable
    {
        yield [1, 2, 3];
        yield [4, 5, 9];
    }
}
