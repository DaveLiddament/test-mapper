<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FirstTestClass extends TestCase
{
    #[Test]
    public function firstTest(): void
    {
        self::assertTrue(true);
    }
}

class SecondTestClass extends TestCase
{
    #[Test]
    public function secondTest(): void
    {
        self::assertTrue(true);
    }
}
