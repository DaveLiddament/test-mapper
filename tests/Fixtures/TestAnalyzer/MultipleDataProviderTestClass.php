<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MultipleDataProviderTestClass extends TestCase
{
    #[Test]
    #[DataProvider('providerOne')]
    #[DataProvider('providerTwo')]
    public function itHasMultipleProviders(int $value): void
    {
        self::assertGreaterThan(0, $value);
    }

    /**
     * @return iterable<array{int}>
     */
    public static function providerOne(): iterable
    {
        yield [1];
        yield [2];
    }

    /**
     * @return iterable<array{int}>
     */
    public static function providerTwo(): iterable
    {
        yield [3];
        yield [4];
    }
}
