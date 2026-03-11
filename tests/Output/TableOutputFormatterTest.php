<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Output;

use DaveLiddament\TestMapper\Model\ChangedSpecFile;
use DaveLiddament\TestMapper\Model\ChangedTestMethod;
use DaveLiddament\TestMapper\Model\FileChangeType;
use DaveLiddament\TestMapper\Output\TableOutputFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

#[CoversClass(TableOutputFormatter::class)]
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
    public function itRendersTestsTable(): void
    {
        $this->formatter->format(
            [new ChangedTestMethod('App\\Checks\\FooCheck', 'it_works', ['JIRA-123'])],
            [],
            $this->output,
        );

        $display = $this->output->fetch();
        self::assertStringContainsString('Test', $display);
        self::assertStringContainsString('Tickets', $display);
        self::assertStringContainsString('App\\Checks\\FooCheck::it_works', $display);
        self::assertStringContainsString('JIRA-123', $display);
    }

    #[Test]
    public function itRendersSpecsTable(): void
    {
        $this->formatter->format(
            [],
            [new ChangedSpecFile(FileChangeType::Added, 'auth/login.md')],
            $this->output,
        );

        $display = $this->output->fetch();
        self::assertStringContainsString('Change Type', $display);
        self::assertStringContainsString('File Path', $display);
        self::assertStringContainsString('added', $display);
        self::assertStringContainsString('auth/login.md', $display);
    }

    #[Test]
    public function itRendersEmptyOutputWhenNoChanges(): void
    {
        $this->formatter->format([], [], $this->output);

        self::assertSame('', $this->output->fetch());
    }

    #[Test]
    public function itRendersTestsWithMultipleTickets(): void
    {
        $this->formatter->format(
            [new ChangedTestMethod('App\\Tests\\FooTest', 'it_works', ['JIRA-1', 'JIRA-2'])],
            [],
            $this->output,
        );

        $display = $this->output->fetch();
        self::assertStringContainsString('JIRA-1', $display);
        self::assertStringContainsString('JIRA-2', $display);
    }

    #[Test]
    public function itRendersTestsWithNoTickets(): void
    {
        $this->formatter->format(
            [new ChangedTestMethod('App\\Tests\\FooTest', 'it_works')],
            [],
            $this->output,
        );

        $display = $this->output->fetch();
        self::assertStringContainsString('App\\Tests\\FooTest::it_works', $display);
    }

    #[Test]
    public function itRendersBothTestsAndSpecs(): void
    {
        $this->formatter->format(
            [new ChangedTestMethod('App\\Tests\\FooTest', 'it_works')],
            [new ChangedSpecFile(FileChangeType::Modified, 'spec.md')],
            $this->output,
        );

        $display = $this->output->fetch();
        self::assertStringContainsString('App\\Tests\\FooTest::it_works', $display);
        self::assertStringContainsString('spec.md', $display);
    }
}
