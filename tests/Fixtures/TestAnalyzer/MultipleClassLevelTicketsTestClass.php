<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('auth/login')]
#[Ticket('auth/session')]
final class MultipleClassLevelTicketsTestClass extends TestCase
{
    #[Test]
    public function itInheritsBothClassTickets(): void
    {
        self::assertTrue(true);
    }
}
