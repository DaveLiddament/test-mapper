<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Command;

use DaveLiddament\TestMapper\ChangedTestFinder;
use DaveLiddament\TestMapper\Command\FindChangedTestsCommand;
use DaveLiddament\TestMapper\Model\ChangedSpecFile;
use DaveLiddament\TestMapper\Model\ChangedTestMethod;
use DaveLiddament\TestMapper\Model\FileChangeType;
use DaveLiddament\TestMapper\Output\JsonOutputFormatter;
use DaveLiddament\TestMapper\Output\TableOutputFormatter;
use DaveLiddament\TestMapper\Specs\ChangedSpecsFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(FindChangedTestsCommand::class)]
final class FindChangedTestsCommandTest extends TestCase
{
    /** @var array<string, \DaveLiddament\TestMapper\Output\OutputFormatter> */
    private array $formatters;

    protected function setUp(): void
    {
        $this->formatters = [
            'table' => new TableOutputFormatter(),
            'json' => new JsonOutputFormatter(),
        ];
    }

    #[Test]
    public function itOutputsChangedTestMethods(): void
    {
        $changedTestFinder = static::createMock(ChangedTestFinder::class);
        $changedTestFinder->expects(self::once())
            ->method('findChangedTests')
            ->with('main')
            ->willReturn([
                new ChangedTestMethod('App\\Tests\\FooTest', 'it_works'),
                new ChangedTestMethod('App\\Tests\\BarTest', 'it_also_works'),
            ]);

        $command = new FindChangedTestsCommand($changedTestFinder, null, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        $output = $tester->getDisplay();
        self::assertStringContainsString('App\\Tests\\FooTest::it_works', $output);
        self::assertStringContainsString('App\\Tests\\BarTest::it_also_works', $output);
    }

    #[Test]
    public function itUsesCustomBranch(): void
    {
        $changedTestFinder = static::createMock(ChangedTestFinder::class);
        $changedTestFinder->expects(self::once())
            ->method('findChangedTests')
            ->with('develop')
            ->willReturn([]);

        $command = new FindChangedTestsCommand($changedTestFinder, null, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--branch' => 'develop']);

        self::assertSame(0, $tester->getStatusCode());
    }

    #[Test]
    public function itOutputsNothingWhenNoChanges(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([]);

        $command = new FindChangedTestsCommand($changedTestFinder, null, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertSame('', $tester->getDisplay());
    }

    #[Test]
    public function itOutputsSpecChangesOnly(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([]);

        $changedSpecsFinder = static::createMock(ChangedSpecsFinder::class);
        $changedSpecsFinder->expects(self::once())
            ->method('findChangedSpecs')
            ->with('main', 'specs')
            ->willReturn([
                new ChangedSpecFile(FileChangeType::Added, 'auth/login'),
            ]);

        $command = new FindChangedTestsCommand($changedTestFinder, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--specs-dir' => 'specs']);

        self::assertSame(0, $tester->getStatusCode());
        $output = $tester->getDisplay();
        self::assertStringContainsString('added', $output);
        self::assertStringContainsString('auth/login', $output);
    }

    #[Test]
    public function itDoesNotOutputSpecsWhenOptionNotProvided(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([]);

        $changedSpecsFinder = static::createMock(ChangedSpecsFinder::class);
        $changedSpecsFinder->expects(self::never())
            ->method('findChangedSpecs');

        $command = new FindChangedTestsCommand($changedTestFinder, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertSame('', $tester->getDisplay());
    }

    #[Test]
    public function itOutputsTestsThenSpecs(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([
            new ChangedTestMethod('App\\Tests\\FooTest', 'it_works'),
        ]);

        $changedSpecsFinder = static::createStub(ChangedSpecsFinder::class);
        $changedSpecsFinder->method('findChangedSpecs')->willReturn([
            new ChangedSpecFile(FileChangeType::Modified, 'auth/login'),
        ]);

        $command = new FindChangedTestsCommand($changedTestFinder, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--specs-dir' => 'specs']);

        $output = $tester->getDisplay();
        $testPos = strpos($output, 'App\\Tests\\FooTest::it_works');
        $specPos = strpos($output, 'auth/login');
        self::assertNotFalse($testPos);
        self::assertNotFalse($specPos);
        self::assertGreaterThan($testPos, $specPos);
    }

    #[Test]
    public function itOutputsMultipleSpecChanges(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([]);

        $changedSpecsFinder = static::createStub(ChangedSpecsFinder::class);
        $changedSpecsFinder->method('findChangedSpecs')->willReturn([
            new ChangedSpecFile(FileChangeType::Added, 'new-feature'),
            new ChangedSpecFile(FileChangeType::Modified, 'existing'),
            new ChangedSpecFile(FileChangeType::Deleted, 'removed'),
        ]);

        $command = new FindChangedTestsCommand($changedTestFinder, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--specs-dir' => 'specs']);

        $output = $tester->getDisplay();
        self::assertStringContainsString('new-feature', $output);
        self::assertStringContainsString('existing', $output);
        self::assertStringContainsString('removed', $output);
    }

    #[Test]
    public function itOutputsNestedDirectoryChanges(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([]);

        $changedSpecsFinder = static::createStub(ChangedSpecsFinder::class);
        $changedSpecsFinder->method('findChangedSpecs')->willReturn([
            new ChangedSpecFile(FileChangeType::Modified, 'auth/login/flow'),
        ]);

        $command = new FindChangedTestsCommand($changedTestFinder, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--specs-dir' => 'specs']);

        $output = $tester->getDisplay();
        self::assertStringContainsString('auth/login/flow', $output);
    }

    #[Test]
    public function itOutputsNothingForSpecsWhenNoSpecChanges(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([]);

        $changedSpecsFinder = static::createStub(ChangedSpecsFinder::class);
        $changedSpecsFinder->method('findChangedSpecs')->willReturn([]);

        $command = new FindChangedTestsCommand($changedTestFinder, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--specs-dir' => 'specs']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertSame('', $tester->getDisplay());
    }

    #[Test]
    public function itWorksWithCustomBranchAndSpecsDir(): void
    {
        $changedTestFinder = static::createMock(ChangedTestFinder::class);
        $changedTestFinder->expects(self::once())
            ->method('findChangedTests')
            ->with('develop')
            ->willReturn([]);

        $changedSpecsFinder = static::createMock(ChangedSpecsFinder::class);
        $changedSpecsFinder->expects(self::once())
            ->method('findChangedSpecs')
            ->with('develop', 'specs')
            ->willReturn([]);

        $command = new FindChangedTestsCommand($changedTestFinder, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--branch' => 'develop', '--specs-dir' => 'specs']);

        self::assertSame(0, $tester->getStatusCode());
    }

    #[Test]
    public function itOutputsDeletedSpecFiles(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([]);

        $changedSpecsFinder = static::createStub(ChangedSpecsFinder::class);
        $changedSpecsFinder->method('findChangedSpecs')->willReturn([
            new ChangedSpecFile(FileChangeType::Deleted, 'old-feature'),
        ]);

        $command = new FindChangedTestsCommand($changedTestFinder, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--specs-dir' => 'specs']);

        $output = $tester->getDisplay();
        self::assertStringContainsString('deleted', $output);
        self::assertStringContainsString('old-feature', $output);
    }

    #[Test]
    public function itOutputsJsonFormat(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([
            new ChangedTestMethod('App\\Tests\\FooTest', 'it_works', ['JIRA-123']),
        ]);

        $changedSpecsFinder = static::createStub(ChangedSpecsFinder::class);
        $changedSpecsFinder->method('findChangedSpecs')->willReturn([
            new ChangedSpecFile(FileChangeType::Added, 'feature'),
        ]);

        $command = new FindChangedTestsCommand($changedTestFinder, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--format' => 'json', '--specs-dir' => 'specs']);

        $data = json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('App\\Tests\\FooTest::it_works', $data['tests'][0]['name']);
        self::assertSame(['JIRA-123'], $data['tests'][0]['ticketIds']);
        self::assertSame('added', $data['specs'][0]['changeType']);
        self::assertSame('feature', $data['specs'][0]['filePath']);
    }

    #[Test]
    public function itFallsBackToTableFormatterForUnknownFormat(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([
            new ChangedTestMethod('App\\Tests\\FooTest', 'it_works'),
        ]);

        $command = new FindChangedTestsCommand($changedTestFinder, null, []);
        $tester = new CommandTester($command);
        $tester->execute(['--format' => 'unknown']);

        $output = $tester->getDisplay();
        self::assertStringContainsString('App\\Tests\\FooTest::it_works', $output);
    }
}
