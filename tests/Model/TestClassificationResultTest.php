<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Model;

use DaveLiddament\TestMapper\Model\ClassifiedTest;
use DaveLiddament\TestMapper\Model\TestClassificationResult;
use DaveLiddament\TestMapper\Model\TestStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestClassificationResult::class)]
#[Ticket('006-classified-test')]
final class TestClassificationResultTest extends TestCase
{
    #[Test]
    public function exitCodeZeroWhenAllOk(): void
    {
        $result = new TestClassificationResult(
            noTest: [],
            unexpectedChange: [],
            noTickets: [],
            ok: [new ClassifiedTest('Test::foo', TestStatus::Ok, ['spec'], ['spec'])],
        );

        self::assertSame(0, $result->getExitCode());
    }

    #[Test]
    public function exitCodeOneWhenNoTickets(): void
    {
        $result = new TestClassificationResult(
            noTest: [],
            unexpectedChange: [],
            noTickets: [new ClassifiedTest('Test::foo', TestStatus::NoTickets, [], [])],
            ok: [],
        );

        self::assertSame(1, $result->getExitCode());
    }

    #[Test]
    public function exitCodeTwoWhenUnexpectedChange(): void
    {
        $result = new TestClassificationResult(
            noTest: [],
            unexpectedChange: [new ClassifiedTest('Test::foo', TestStatus::UnexpectedChange, ['JIRA-1'], [])],
            noTickets: [],
            ok: [],
        );

        self::assertSame(2, $result->getExitCode());
    }

    #[Test]
    public function exitCodeThreeWhenNoTicketsAndUnexpectedChange(): void
    {
        $result = new TestClassificationResult(
            noTest: [],
            unexpectedChange: [new ClassifiedTest('Test::foo', TestStatus::UnexpectedChange, ['JIRA-1'], [])],
            noTickets: [new ClassifiedTest('Test::bar', TestStatus::NoTickets, [], [])],
            ok: [],
        );

        self::assertSame(3, $result->getExitCode());
    }

    #[Test]
    public function exitCodeFourWhenNoTest(): void
    {
        $result = new TestClassificationResult(
            noTest: ['auth/login'],
            unexpectedChange: [],
            noTickets: [],
            ok: [],
        );

        self::assertSame(4, $result->getExitCode());
    }

    #[Test]
    public function exitCodeFiveWhenNoTestAndNoTickets(): void
    {
        $result = new TestClassificationResult(
            noTest: ['auth/login'],
            unexpectedChange: [],
            noTickets: [new ClassifiedTest('Test::foo', TestStatus::NoTickets, [], [])],
            ok: [],
        );

        self::assertSame(5, $result->getExitCode());
    }

    #[Test]
    public function exitCodeSixWhenNoTestAndUnexpectedChange(): void
    {
        $result = new TestClassificationResult(
            noTest: ['auth/login'],
            unexpectedChange: [new ClassifiedTest('Test::foo', TestStatus::UnexpectedChange, ['JIRA-1'], [])],
            noTickets: [],
            ok: [],
        );

        self::assertSame(6, $result->getExitCode());
    }

    #[Test]
    public function exitCodeSevenWhenAllProblems(): void
    {
        $result = new TestClassificationResult(
            noTest: ['auth/login'],
            unexpectedChange: [new ClassifiedTest('Test::foo', TestStatus::UnexpectedChange, ['JIRA-1'], [])],
            noTickets: [new ClassifiedTest('Test::bar', TestStatus::NoTickets, [], [])],
            ok: [],
        );

        self::assertSame(7, $result->getExitCode());
    }

    #[Test]
    public function exitCodeZeroWhenEmpty(): void
    {
        $result = new TestClassificationResult(
            noTest: [],
            unexpectedChange: [],
            noTickets: [],
            ok: [],
        );

        self::assertSame(0, $result->getExitCode());
    }
}
