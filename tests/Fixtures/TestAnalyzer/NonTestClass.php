<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer;

final class NonTestClass
{
    public function testSomething(): void
    {
        // This has the test prefix but no #[Test] attribute
    }

    public function anotherMethod(): void
    {
    }
}
