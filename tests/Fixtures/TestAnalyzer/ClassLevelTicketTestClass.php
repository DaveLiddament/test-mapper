<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('auth/login')]
final class ClassLevelTicketTestClass extends TestCase
{
    #[Test]
    public function itInheritsClassTicket(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    #[Ticket('auth/session')]
    public function itMergesClassAndMethodTickets(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    #[DataProvider('dataProvider')]
    public function itWorksWithDataProvider(int $value): void
    {
        self::assertSame($value, $value);
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
