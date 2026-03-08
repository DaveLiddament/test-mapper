<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AttributeTestClass extends TestCase
{
    /**
     * This is a docblock.
     */
    #[Test]
    #[DataProvider('dataProvider')]
    public function itHasDataProvider(int $value): void
    {
        self::assertGreaterThan(0, $value);
    }

    /**
     * @return iterable<array{int}>
     */
    public static function dataProvider(): iterable
    {
        yield [1];
        yield [2];
    }
}
