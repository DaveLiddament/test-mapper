<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests;

use DaveLiddament\TestMapper\Model\ChangedSpecFile;
use DaveLiddament\TestMapper\Model\ChangedTestMethod;
use DaveLiddament\TestMapper\Model\FileChangeType;
use DaveLiddament\TestMapper\TestClassifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestClassifier::class)]
#[Ticket('014-test-classifier')]
final class TestClassifierTest extends TestCase
{
    private TestClassifier $classifier;

    protected function setUp(): void
    {
        $this->classifier = new TestClassifier();
    }

    #[Test]
    public function allOkWhenTicketsMatchSpecs(): void
    {
        $tests = [
            new ChangedTestMethod('App\\Tests\\FooTest', 'bar', ['auth/login']),
        ];
        $specs = [
            new ChangedSpecFile(FileChangeType::Modified, 'auth/login'),
        ];

        $result = $this->classifier->classify($tests, $specs);

        self::assertSame([], $result->noTest);
        self::assertSame([], $result->unexpectedChange);
        self::assertSame([], $result->noTickets);
        self::assertCount(1, $result->ok);
        self::assertSame('App\\Tests\\FooTest::bar', $result->ok[0]->testName);
        self::assertSame(['auth/login'], $result->ok[0]->ticketIds);
        self::assertSame(['auth/login'], $result->ok[0]->matchingSpecs);
    }

    #[Test]
    public function allNoTicketsWhenTestsHaveNoTicketIds(): void
    {
        $tests = [
            new ChangedTestMethod('App\\Tests\\FooTest', 'bar'),
            new ChangedTestMethod('App\\Tests\\BazTest', 'qux'),
        ];
        $specs = [
            new ChangedSpecFile(FileChangeType::Modified, 'auth/login'),
        ];

        $result = $this->classifier->classify($tests, $specs);

        self::assertSame(['auth/login'], $result->noTest);
        self::assertSame([], $result->unexpectedChange);
        self::assertCount(2, $result->noTickets);
        self::assertSame([], $result->ok);
    }

    #[Test]
    public function allUnexpectedChangeWhenTicketsDontMatchSpecs(): void
    {
        $tests = [
            new ChangedTestMethod('App\\Tests\\FooTest', 'bar', ['JIRA-1']),
        ];
        $specs = [
            new ChangedSpecFile(FileChangeType::Modified, 'auth/login'),
        ];

        $result = $this->classifier->classify($tests, $specs);

        self::assertSame(['auth/login'], $result->noTest);
        self::assertCount(1, $result->unexpectedChange);
        self::assertSame('App\\Tests\\FooTest::bar', $result->unexpectedChange[0]->testName);
        self::assertSame(['JIRA-1'], $result->unexpectedChange[0]->ticketIds);
        self::assertSame([], $result->unexpectedChange[0]->matchingSpecs);
        self::assertSame([], $result->noTickets);
        self::assertSame([], $result->ok);
    }

    #[Test]
    public function noTestWhenSpecHasNoCorrespondingTest(): void
    {
        $tests = [];
        $specs = [
            new ChangedSpecFile(FileChangeType::Added, 'auth/login'),
            new ChangedSpecFile(FileChangeType::Modified, 'payments/checkout'),
        ];

        $result = $this->classifier->classify($tests, $specs);

        self::assertSame(['auth/login', 'payments/checkout'], $result->noTest);
        self::assertSame([], $result->unexpectedChange);
        self::assertSame([], $result->noTickets);
        self::assertSame([], $result->ok);
    }

    #[Test]
    public function mixedStatuses(): void
    {
        $tests = [
            new ChangedTestMethod('App\\Tests\\OkTest', 'bar', ['auth/login']),
            new ChangedTestMethod('App\\Tests\\UnexpectedTest', 'baz', ['JIRA-99']),
            new ChangedTestMethod('App\\Tests\\NoTicketTest', 'qux'),
        ];
        $specs = [
            new ChangedSpecFile(FileChangeType::Modified, 'auth/login'),
            new ChangedSpecFile(FileChangeType::Added, 'unmatched/spec'),
        ];

        $result = $this->classifier->classify($tests, $specs);

        self::assertSame(['unmatched/spec'], $result->noTest);
        self::assertCount(1, $result->unexpectedChange);
        self::assertSame('App\\Tests\\UnexpectedTest::baz', $result->unexpectedChange[0]->testName);
        self::assertCount(1, $result->noTickets);
        self::assertSame('App\\Tests\\NoTicketTest::qux', $result->noTickets[0]->testName);
        self::assertCount(1, $result->ok);
        self::assertSame('App\\Tests\\OkTest::bar', $result->ok[0]->testName);
    }

    #[Test]
    public function partialMatchTestHasTwoTicketsOnlyOneMatches(): void
    {
        $tests = [
            new ChangedTestMethod('App\\Tests\\FooTest', 'bar', ['auth/login', 'JIRA-1']),
        ];
        $specs = [
            new ChangedSpecFile(FileChangeType::Modified, 'auth/login'),
        ];

        $result = $this->classifier->classify($tests, $specs);

        self::assertSame([], $result->noTest);
        self::assertSame([], $result->unexpectedChange);
        self::assertSame([], $result->noTickets);
        self::assertCount(1, $result->ok);
        self::assertSame(['auth/login', 'JIRA-1'], $result->ok[0]->ticketIds);
        self::assertSame(['auth/login'], $result->ok[0]->matchingSpecs);
    }

    #[Test]
    public function alphabeticalSorting(): void
    {
        $tests = [
            new ChangedTestMethod('App\\Tests\\ZTest', 'foo'),
            new ChangedTestMethod('App\\Tests\\ATest', 'foo'),
            new ChangedTestMethod('App\\Tests\\MTest', 'foo'),
        ];
        $specs = [
            new ChangedSpecFile(FileChangeType::Modified, 'z-spec'),
            new ChangedSpecFile(FileChangeType::Modified, 'a-spec'),
        ];

        $result = $this->classifier->classify($tests, $specs);

        self::assertSame(['a-spec', 'z-spec'], $result->noTest);
        self::assertSame('App\\Tests\\ATest::foo', $result->noTickets[0]->testName);
        self::assertSame('App\\Tests\\MTest::foo', $result->noTickets[1]->testName);
        self::assertSame('App\\Tests\\ZTest::foo', $result->noTickets[2]->testName);
    }

    #[Test]
    public function multipleMatchingSpecsAreSorted(): void
    {
        $tests = [
            new ChangedTestMethod('App\\Tests\\FooTest', 'bar', ['z-spec', 'a-spec']),
        ];
        $specs = [
            new ChangedSpecFile(FileChangeType::Modified, 'z-spec'),
            new ChangedSpecFile(FileChangeType::Modified, 'a-spec'),
        ];

        $result = $this->classifier->classify($tests, $specs);

        self::assertCount(1, $result->ok);
        self::assertSame(['a-spec', 'z-spec'], $result->ok[0]->matchingSpecs);
    }

    #[Test]
    public function multipleOkTestsAreSortedAlphabetically(): void
    {
        $tests = [
            new ChangedTestMethod('App\\Tests\\ZTest', 'foo', ['spec']),
            new ChangedTestMethod('App\\Tests\\ATest', 'foo', ['spec']),
            new ChangedTestMethod('App\\Tests\\MTest', 'foo', ['spec']),
        ];
        $specs = [
            new ChangedSpecFile(FileChangeType::Modified, 'spec'),
        ];

        $result = $this->classifier->classify($tests, $specs);

        self::assertCount(3, $result->ok);
        self::assertSame('App\\Tests\\ATest::foo', $result->ok[0]->testName);
        self::assertSame('App\\Tests\\MTest::foo', $result->ok[1]->testName);
        self::assertSame('App\\Tests\\ZTest::foo', $result->ok[2]->testName);
    }

    #[Test]
    public function emptyInputs(): void
    {
        $result = $this->classifier->classify([], []);

        self::assertSame([], $result->noTest);
        self::assertSame([], $result->unexpectedChange);
        self::assertSame([], $result->noTickets);
        self::assertSame([], $result->ok);
        self::assertSame(0, $result->getExitCode());
    }

    #[Test]
    public function noSpecsButTestsWithTicketsAreAllUnexpectedChange(): void
    {
        $tests = [
            new ChangedTestMethod('App\\Tests\\FooTest', 'bar', ['JIRA-1']),
            new ChangedTestMethod('App\\Tests\\BazTest', 'qux', ['JIRA-2']),
        ];

        $result = $this->classifier->classify($tests, []);

        self::assertSame([], $result->noTest);
        self::assertCount(2, $result->unexpectedChange);
        self::assertSame('App\\Tests\\BazTest::qux', $result->unexpectedChange[0]->testName);
        self::assertSame('App\\Tests\\FooTest::bar', $result->unexpectedChange[1]->testName);
        self::assertSame([], $result->noTickets);
        self::assertSame([], $result->ok);
    }
}
