<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Model;

use DaveLiddament\TestMapper\Model\ClassifiedTest;
use DaveLiddament\TestMapper\Model\TestStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassifiedTest::class)]
#[Ticket('006-classified-test')]
final class ClassifiedTestTest extends TestCase
{
    #[Test]
    public function itStoresProperties(): void
    {
        $classifiedTest = new ClassifiedTest(
            'App\\Tests\\FooTest::bar',
            TestStatus::Ok,
            ['auth/login'],
            ['auth/login'],
        );

        self::assertSame('App\\Tests\\FooTest::bar', $classifiedTest->testName);
        self::assertSame(TestStatus::Ok, $classifiedTest->status);
        self::assertSame(['auth/login'], $classifiedTest->ticketIds);
        self::assertSame(['auth/login'], $classifiedTest->matchingSpecs);
    }

    #[Test]
    public function itStoresEmptyArrays(): void
    {
        $classifiedTest = new ClassifiedTest(
            'App\\Tests\\FooTest::bar',
            TestStatus::NoTickets,
            [],
            [],
        );

        self::assertSame([], $classifiedTest->ticketIds);
        self::assertSame([], $classifiedTest->matchingSpecs);
    }
}
