<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

final class TicketTestClass extends TestCase
{
    #[Test]
    #[Ticket('JIRA-123')]
    public function itHasSingleTicket(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    #[Ticket('JIRA-456')]
    #[Ticket('JIRA-789')]
    public function itHasMultipleTickets(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    public function itHasNoTicket(): void
    {
        self::assertTrue(true);
    }
}
