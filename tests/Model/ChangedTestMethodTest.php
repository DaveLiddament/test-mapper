<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Model;

use DaveLiddament\TestMapper\Model\ChangedTestMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChangedTestMethod::class)]
final class ChangedTestMethodTest extends TestCase
{
    #[Test]
    public function itReturnsFullyQualifiedName(): void
    {
        $method = new ChangedTestMethod('App\\Tests\\FooTest', 'it_works');

        self::assertSame('App\\Tests\\FooTest', $method->fullyQualifiedClassName);
        self::assertSame('it_works', $method->methodName);
        self::assertSame('App\\Tests\\FooTest::it_works', $method->getFullyQualifiedName());
    }
}
