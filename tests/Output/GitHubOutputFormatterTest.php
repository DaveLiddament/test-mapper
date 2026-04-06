<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Output;

use DaveLiddament\TestMapper\Model\ChangedTestMethod;
use DaveLiddament\TestMapper\Model\ClassifiedTest;
use DaveLiddament\TestMapper\Model\TestClassificationResult;
use DaveLiddament\TestMapper\Model\TestStatus;
use DaveLiddament\TestMapper\Output\GitHubOutputFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

#[CoversClass(GitHubOutputFormatter::class)]
#[Ticket('021-github-output-format')]
final class GitHubOutputFormatterTest extends TestCase
{
    private GitHubOutputFormatter $formatter;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->formatter = new GitHubOutputFormatter();
        $this->output = new BufferedOutput();
    }

    #[Test]
    public function itOutputsNoTicketsAsError(): void
    {
        $result = new TestClassificationResult(
            noTest: [],
            unexpectedChange: [],
            noTickets: [new ClassifiedTest('App\\Tests\\FooTest::bar', TestStatus::NoTickets, [], [])],
            ok: [],
        );

        $this->formatter->format([], $result, $this->output);

        self::assertSame("::error::No Tickets: App\\Tests\\FooTest::bar\n", $this->output->fetch());
    }

    #[Test]
    public function itOutputsUnexpectedChangeAsError(): void
    {
        $result = new TestClassificationResult(
            noTest: [],
            unexpectedChange: [new ClassifiedTest('App\\Tests\\FooTest::bar', TestStatus::UnexpectedChange, ['JIRA-1'], [])],
            noTickets: [],
            ok: [],
        );

        $this->formatter->format([], $result, $this->output);

        self::assertSame("::error::Unexpected Change: App\\Tests\\FooTest::bar (tickets: JIRA-1)\n", $this->output->fetch());
    }

    #[Test]
    public function itOutputsNoTestAsWarning(): void
    {
        $result = new TestClassificationResult(
            noTest: ['auth/login'],
            unexpectedChange: [],
            noTickets: [],
            ok: [],
        );

        $this->formatter->format([], $result, $this->output);

        self::assertSame("::warning::No Test: auth/login\n", $this->output->fetch());
    }

    #[Test]
    public function itOutputsNothingForOkTests(): void
    {
        $result = new TestClassificationResult(
            noTest: [],
            unexpectedChange: [],
            noTickets: [],
            ok: [new ClassifiedTest('App\\Tests\\FooTest::bar', TestStatus::Ok, ['auth/login'], ['auth/login'])],
        );

        $this->formatter->format([], $result, $this->output);

        self::assertSame('', $this->output->fetch());
    }

    #[Test]
    public function itOutputsAllCategoriesTogether(): void
    {
        $result = new TestClassificationResult(
            noTest: ['auth/login'],
            unexpectedChange: [new ClassifiedTest('App\\Tests\\UnexpectedTest::baz', TestStatus::UnexpectedChange, ['JIRA-99'], [])],
            noTickets: [new ClassifiedTest('App\\Tests\\NoTicketTest::qux', TestStatus::NoTickets, [], [])],
            ok: [new ClassifiedTest('App\\Tests\\OkTest::bar', TestStatus::Ok, ['auth/login'], ['auth/login'])],
        );

        $this->formatter->format([], $result, $this->output);

        $display = $this->output->fetch();
        self::assertStringContainsString('::error::No Tickets: App\\Tests\\NoTicketTest::qux', $display);
        self::assertStringContainsString('::error::Unexpected Change: App\\Tests\\UnexpectedTest::baz (tickets: JIRA-99)', $display);
        self::assertStringContainsString('::warning::No Test: auth/login', $display);
        self::assertStringNotContainsString('OkTest', $display);
    }

    #[Test]
    public function itOutputsNothingWhenAllEmpty(): void
    {
        $result = new TestClassificationResult([], [], [], []);

        $this->formatter->format([], $result, $this->output);

        self::assertSame('', $this->output->fetch());
    }

    #[Test]
    public function itOutputsLegacyFormatAsNotice(): void
    {
        $this->formatter->format(
            [
                new ChangedTestMethod('App\\Tests\\FooTest', 'it_works', filePath: 'tests/FooTest.php'),
                new ChangedTestMethod('App\\Tests\\BarTest', 'it_also_works', filePath: 'tests/BarTest.php'),
            ],
            null,
            $this->output,
        );

        $display = $this->output->fetch();
        self::assertStringContainsString('::notice::Changed test: App\\Tests\\FooTest::it_works', $display);
        self::assertStringContainsString('::notice::Changed test: App\\Tests\\BarTest::it_also_works', $display);
    }

    #[Test]
    public function itOutputsNothingForLegacyWithNoChanges(): void
    {
        $this->formatter->format([], null, $this->output);

        self::assertSame('', $this->output->fetch());
    }

    #[Test]
    public function itOutputsMultipleTicketsInUnexpectedChange(): void
    {
        $result = new TestClassificationResult(
            noTest: [],
            unexpectedChange: [new ClassifiedTest('App\\Tests\\FooTest::bar', TestStatus::UnexpectedChange, ['JIRA-1', 'JIRA-2'], [])],
            noTickets: [],
            ok: [],
        );

        $this->formatter->format([], $result, $this->output);

        self::assertStringContainsString('(tickets: JIRA-1, JIRA-2)', $this->output->fetch());
    }
}
