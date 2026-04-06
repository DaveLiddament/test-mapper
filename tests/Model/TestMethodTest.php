<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Model;

use DaveLiddament\TestMapper\Model\LineRange;
use DaveLiddament\TestMapper\Model\TestMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestMethod::class)]
#[CoversClass(LineRange::class)]
#[Ticket('002-test-method')]
#[Ticket('003-line-range')]
final class TestMethodTest extends TestCase
{
    #[Test]
    public function itStoresProperties(): void
    {
        $dependentRange = new LineRange(10, 20);
        $method = new TestMethod(
            'App\\Tests\\FooTest',
            'it_works',
            5,
            15,
            'tests/FooTest.php',
            [$dependentRange],
        );

        self::assertSame('App\\Tests\\FooTest', $method->fullyQualifiedClassName);
        self::assertSame('it_works', $method->methodName);
        self::assertSame(5, $method->startLine);
        self::assertSame(15, $method->endLine);
        self::assertSame('tests/FooTest.php', $method->filePath);
        self::assertCount(1, $method->dependentRanges);
        self::assertSame(10, $method->dependentRanges[0]->startLine);
        self::assertSame(20, $method->dependentRanges[0]->endLine);
    }

    #[Test]
    public function itDefaultsToEmptyDependentRanges(): void
    {
        $method = new TestMethod('App\\Tests\\FooTest', 'it_works', 5, 15, 'tests/FooTest.php');

        self::assertSame([], $method->dependentRanges);
    }

    #[Test]
    public function itStoresTicketIds(): void
    {
        $method = new TestMethod(
            'App\\Tests\\FooTest',
            'it_works',
            5,
            15,
            'tests/FooTest.php',
            [],
            ['JIRA-123', 'JIRA-456'],
        );

        self::assertSame(['JIRA-123', 'JIRA-456'], $method->ticketIds);
    }

    #[Test]
    public function itDefaultsToEmptyTicketIds(): void
    {
        $method = new TestMethod('App\\Tests\\FooTest', 'it_works', 5, 15, 'tests/FooTest.php');

        self::assertSame([], $method->ticketIds);
    }

    #[Test]
    public function itReturnsRelativeFilePath(): void
    {
        $method = new TestMethod('App\\Tests\\FooTest', 'it_works', 5, 15, 'tests/FooTest.php');

        self::assertSame('tests/FooTest.php', $method->getRelativeFilePath());
    }
}
