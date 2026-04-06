<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Output;

use DaveLiddament\TestMapper\Model\ChangedTestMethod;
use DaveLiddament\TestMapper\Model\ClassifiedTest;
use DaveLiddament\TestMapper\Model\TestClassificationResult;
use DaveLiddament\TestMapper\Model\TestStatus;
use DaveLiddament\TestMapper\Output\TableOutputFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

#[CoversClass(TableOutputFormatter::class)]
#[Ticket('018-table-output-format')]
final class TableOutputFormatterTest extends TestCase
{
    private TableOutputFormatter $formatter;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->formatter = new TableOutputFormatter();
        $this->output = new BufferedOutput();
    }

    #[Test]
    public function legacyFormatRendersTestsTable(): void
    {
        $this->formatter->format(
            [new ChangedTestMethod('App\\Checks\\FooCheck', 'it_works', ['JIRA-123'])],
            null,
            $this->output,
        );

        $display = $this->output->fetch();
        self::assertStringContainsString('Test', $display);
        self::assertStringContainsString('Tickets', $display);
        self::assertStringContainsString('App\\Checks\\FooCheck::it_works', $display);
        self::assertStringContainsString('JIRA-123', $display);
    }

    #[Test]
    public function legacyFormatRendersEmptyOutputWhenNoChanges(): void
    {
        $this->formatter->format([], null, $this->output);

        self::assertSame('', $this->output->fetch());
    }

    #[Test]
    public function legacyFormatRendersMultipleTickets(): void
    {
        $this->formatter->format(
            [new ChangedTestMethod('App\\Tests\\FooTest', 'it_works', ['JIRA-1', 'JIRA-2'])],
            null,
            $this->output,
        );

        $display = $this->output->fetch();
        self::assertStringContainsString('JIRA-1', $display);
        self::assertStringContainsString('JIRA-2', $display);
    }

    #[Test]
    public function legacyFormatRendersTestsWithNoTickets(): void
    {
        $this->formatter->format(
            [new ChangedTestMethod('App\\Tests\\FooTest', 'it_works')],
            null,
            $this->output,
        );

        $display = $this->output->fetch();
        self::assertStringContainsString('App\\Tests\\FooTest::it_works', $display);
    }

    #[Test]
    public function classifiedFormatRendersEmptyOutputWhenAllEmpty(): void
    {
        $result = new TestClassificationResult([], [], [], []);

        $this->formatter->format([], $result, $this->output);

        self::assertSame('', $this->output->fetch());
    }

    #[Test]
    public function classifiedFormatRendersFourColumnTable(): void
    {
        $result = new TestClassificationResult(
            noTest: ['auth/login'],
            unexpectedChange: [new ClassifiedTest('App\\Tests\\FooTest::bar', TestStatus::UnexpectedChange, ['JIRA-1'], [])],
            noTickets: [new ClassifiedTest('App\\Tests\\BazTest::qux', TestStatus::NoTickets, [], [])],
            ok: [new ClassifiedTest('App\\Tests\\BarTest::foo', TestStatus::Ok, ['auth/login'], ['auth/login'])],
        );

        $this->formatter->format([], $result, $this->output);

        $display = $this->output->fetch();
        self::assertStringContainsString('| Test ', $display);
        self::assertStringContainsString('Tickets', $display);
        self::assertStringContainsString('Specs', $display);
        self::assertStringContainsString('Status', $display);
        self::assertStringContainsString('No Test', $display);
        self::assertStringContainsString('Unexpected Change', $display);
        self::assertStringContainsString('No Tickets', $display);
        self::assertStringContainsString('OK', $display);
        self::assertStringContainsString('auth/login', $display);
        self::assertStringContainsString('JIRA-1', $display);
        self::assertStringContainsString('App\\Tests\\BazTest::qux', $display);
    }

    #[Test]
    public function classifiedFormatNoTestRowsAreYellow(): void
    {
        $result = new TestClassificationResult(
            noTest: ['auth/login'],
            unexpectedChange: [],
            noTickets: [],
            ok: [],
        );

        $decoratedOutput = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
        $this->formatter->format([], $result, $decoratedOutput);

        $display = $decoratedOutput->fetch();
        self::assertStringContainsString("\033[33mauth/login\033[39m", $display);
        self::assertStringContainsString("\033[33mNo Test\033[39m", $display);
    }

    #[Test]
    public function classifiedFormatUnexpectedChangeRowsAreRed(): void
    {
        $result = new TestClassificationResult(
            noTest: [],
            unexpectedChange: [new ClassifiedTest('App\\Tests\\FooTest::bar', TestStatus::UnexpectedChange, ['JIRA-1'], [])],
            noTickets: [],
            ok: [],
        );

        $decoratedOutput = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
        $this->formatter->format([], $result, $decoratedOutput);

        $display = $decoratedOutput->fetch();
        self::assertStringContainsString("\033[37;41mApp\\Tests\\FooTest::bar\033[39;49m", $display);
        self::assertStringContainsString("\033[37;41mJIRA-1\033[39;49m", $display);
        self::assertStringContainsString("\033[37;41mUnexpected Change\033[39;49m", $display);
    }

    #[Test]
    public function classifiedFormatNoTestRowHasCorrectColumnLayout(): void
    {
        $result = new TestClassificationResult(
            noTest: ['auth/login'],
            unexpectedChange: [],
            noTickets: [],
            ok: [],
        );

        $this->formatter->format([], $result, $this->output);

        $display = $this->output->fetch();
        $noTestLine = $this->findLineContaining($display, 'No Test');
        $cells = $this->extractCells($noTestLine);
        self::assertSame('', $cells[0]);
        self::assertSame('', $cells[1]);
        self::assertSame('auth/login', $cells[2]);
        self::assertSame('No Test', $cells[3]);
    }

    #[Test]
    public function classifiedFormatNoTicketsRowHasTestNameInFirstColumn(): void
    {
        $result = new TestClassificationResult(
            noTest: [],
            unexpectedChange: [],
            noTickets: [new ClassifiedTest('App\\Tests\\BazTest::qux', TestStatus::NoTickets, [], [])],
            ok: [],
        );

        $this->formatter->format([], $result, $this->output);

        $display = $this->output->fetch();
        $noTicketsLine = $this->findLineContaining($display, 'No Tickets');
        $cells = $this->extractCells($noTicketsLine);
        self::assertSame('App\\Tests\\BazTest::qux', $cells[0]);
    }

    #[Test]
    public function classifiedFormatOkRowShowsMatchingSpecs(): void
    {
        $result = new TestClassificationResult(
            noTest: [],
            unexpectedChange: [],
            noTickets: [],
            ok: [new ClassifiedTest('App\\Tests\\FooTest::bar', TestStatus::Ok, ['auth/login', 'JIRA-1'], ['auth/login'])],
        );

        $this->formatter->format([], $result, $this->output);

        $display = $this->output->fetch();
        self::assertStringContainsString('App\\Tests\\FooTest::bar', $display);
        self::assertStringContainsString('OK', $display);
        self::assertStringContainsString('auth/login', $display);
    }

    private function findLineContaining(string $output, string $needle): string
    {
        foreach (explode("\n", $output) as $line) {
            if (str_contains($line, $needle)) {
                return $line;
            }
        }

        self::fail("No line containing '{$needle}' found in output");
    }

    /**
     * @return list<string>
     */
    private function extractCells(string $tableLine): array
    {
        $trimmed = trim($tableLine, '|');
        $cells = explode('|', $trimmed);

        return array_map('trim', $cells);
    }
}
