<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer;

use PHPUnit\Framework\TestCase;

final class NoTestMethodsClass extends TestCase
{
    protected function setUp(): void
    {
    }

    public function helperMethod(): void
    {
    }
}
