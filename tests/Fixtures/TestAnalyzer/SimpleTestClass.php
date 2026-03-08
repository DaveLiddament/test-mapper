<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SimpleTestClass extends TestCase
{
    #[Test]
    public function itDoesSomething(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    public function itDoesSomethingElse(): void
    {
        self::assertFalse(false);
    }
}
