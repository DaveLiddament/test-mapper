<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Command;

use DaveLiddament\TestMapper\ChangedTestFinder;
use DaveLiddament\TestMapper\Command\FindChangedTestsCommand;
use DaveLiddament\TestMapper\Config\ConfigLoader;
use DaveLiddament\TestMapper\Model\ChangedSpecFile;
use DaveLiddament\TestMapper\Model\ChangedTestMethod;
use DaveLiddament\TestMapper\Model\FileChangeType;
use DaveLiddament\TestMapper\Output\JsonOutputFormatter;
use DaveLiddament\TestMapper\Output\TableOutputFormatter;
use DaveLiddament\TestMapper\Specs\ChangedSpecsFinder;
use DaveLiddament\TestMapper\TestClassifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(FindChangedTestsCommand::class)]
final class FindChangedTestsCommandTest extends TestCase
{
    /** @var array<string, \DaveLiddament\TestMapper\Output\OutputFormatter> */
    private array $formatters;

    private TestClassifier $testClassifier;

    private ConfigLoader $configLoader;

    protected function setUp(): void
    {
        $this->formatters = [
            'table' => new TableOutputFormatter(),
            'json' => new JsonOutputFormatter(),
        ];
        $this->testClassifier = new TestClassifier();
        $this->configLoader = new ConfigLoader();
    }

    #[Test]
    public function itOutputsChangedTestMethodsWithoutSpecsDir(): void
    {
        $changedTestFinder = static::createMock(ChangedTestFinder::class);
        $changedTestFinder->expects(self::once())
            ->method('findChangedTests')
            ->with('main')
            ->willReturn([
                new ChangedTestMethod('App\\Tests\\FooTest', 'it_works'),
                new ChangedTestMethod('App\\Tests\\BarTest', 'it_also_works'),
            ]);

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, null, $this->formatters);
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

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, null, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--branch' => 'develop']);

        self::assertSame(0, $tester->getStatusCode());
    }

    #[Test]
    public function itOutputsNothingWhenNoChanges(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([]);

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, null, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertSame('', $tester->getDisplay());
    }

    #[Test]
    public function itDoesNotCallSpecsFinderWhenOptionNotProvided(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([]);

        $changedSpecsFinder = static::createMock(ChangedSpecsFinder::class);
        $changedSpecsFinder->expects(self::never())
            ->method('findChangedSpecs');

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertSame('', $tester->getDisplay());
    }

    #[Test]
    public function itReturnsExitCodeZeroWhenAllOk(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([
            new ChangedTestMethod('App\\Tests\\FooTest', 'bar', ['auth/login']),
        ]);

        $changedSpecsFinder = static::createStub(ChangedSpecsFinder::class);
        $changedSpecsFinder->method('findChangedSpecs')->willReturn([
            new ChangedSpecFile(FileChangeType::Modified, 'auth/login'),
        ]);

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--specs-dir' => 'specs']);

        self::assertSame(0, $tester->getStatusCode());
        $output = $tester->getDisplay();
        self::assertStringContainsString('OK', $output);
    }

    #[Test]
    public function itReturnsExitCodeOneWhenNoTickets(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([
            new ChangedTestMethod('App\\Tests\\FooTest', 'bar'),
        ]);

        $changedSpecsFinder = static::createStub(ChangedSpecsFinder::class);
        $changedSpecsFinder->method('findChangedSpecs')->willReturn([]);

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--specs-dir' => 'specs']);

        self::assertSame(1, $tester->getStatusCode());
        $output = $tester->getDisplay();
        self::assertStringContainsString('No Tickets', $output);
    }

    #[Test]
    public function itReturnsExitCodeTwoWhenUnexpectedChange(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([
            new ChangedTestMethod('App\\Tests\\FooTest', 'bar', ['JIRA-1']),
        ]);

        $changedSpecsFinder = static::createStub(ChangedSpecsFinder::class);
        $changedSpecsFinder->method('findChangedSpecs')->willReturn([]);

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--specs-dir' => 'specs']);

        self::assertSame(2, $tester->getStatusCode());
        $output = $tester->getDisplay();
        self::assertStringContainsString('Unexpected Change', $output);
    }

    #[Test]
    public function itReturnsExitCodeFourWhenNoTest(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([]);

        $changedSpecsFinder = static::createStub(ChangedSpecsFinder::class);
        $changedSpecsFinder->method('findChangedSpecs')->willReturn([
            new ChangedSpecFile(FileChangeType::Added, 'auth/login'),
        ]);

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--specs-dir' => 'specs']);

        self::assertSame(4, $tester->getStatusCode());
        $output = $tester->getDisplay();
        self::assertStringContainsString('No Test', $output);
    }

    #[Test]
    public function itReturnsExitCodeSevenWhenAllProblems(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([
            new ChangedTestMethod('App\\Tests\\NoTicketTest', 'foo'),
            new ChangedTestMethod('App\\Tests\\UnexpectedTest', 'bar', ['JIRA-99']),
        ]);

        $changedSpecsFinder = static::createStub(ChangedSpecsFinder::class);
        $changedSpecsFinder->method('findChangedSpecs')->willReturn([
            new ChangedSpecFile(FileChangeType::Added, 'unmatched/spec'),
        ]);

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--specs-dir' => 'specs']);

        self::assertSame(7, $tester->getStatusCode());
    }

    #[Test]
    public function itOutputsJsonFormatWithClassification(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([
            new ChangedTestMethod('App\\Tests\\FooTest', 'it_works', ['auth/login']),
        ]);

        $changedSpecsFinder = static::createStub(ChangedSpecsFinder::class);
        $changedSpecsFinder->method('findChangedSpecs')->willReturn([
            new ChangedSpecFile(FileChangeType::Added, 'auth/login'),
        ]);

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--format' => 'json', '--specs-dir' => 'specs']);

        $data = json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame([], $data['noTest']);
        self::assertSame([], $data['unexpectedChange']);
        self::assertSame([], $data['noTickets']);
        self::assertCount(1, $data['ok']);
        self::assertSame('App\\Tests\\FooTest::it_works', $data['ok'][0]['test']);
    }

    #[Test]
    public function itOutputsJsonFormatWithoutSpecsDir(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([
            new ChangedTestMethod('App\\Tests\\FooTest', 'it_works', ['JIRA-123']),
        ]);

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, null, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--format' => 'json']);

        $data = json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('App\\Tests\\FooTest::it_works', $data['tests'][0]['name']);
        self::assertSame(['JIRA-123'], $data['tests'][0]['ticketIds']);
    }

    #[Test]
    public function itFallsBackToTableFormatterForUnknownFormat(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([
            new ChangedTestMethod('App\\Tests\\FooTest', 'it_works'),
        ]);

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, formatters: []);
        $tester = new CommandTester($command);
        $tester->execute(['--format' => 'unknown']);

        $output = $tester->getDisplay();
        self::assertStringContainsString('App\\Tests\\FooTest::it_works', $output);
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

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--branch' => 'develop', '--specs-dir' => 'specs']);

        self::assertSame(0, $tester->getStatusCode());
    }

    #[Test]
    public function itReturnsZeroWithoutSpecsDirEvenWithNoTickets(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([
            new ChangedTestMethod('App\\Tests\\FooTest', 'bar'),
        ]);

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, null, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
    }

    #[Test]
    public function itPassesIncludeUntrackedToFinders(): void
    {
        $changedTestFinder = static::createMock(ChangedTestFinder::class);
        $changedTestFinder->expects(self::once())
            ->method('findChangedTests')
            ->with('main', true)
            ->willReturn([]);

        $changedSpecsFinder = static::createMock(ChangedSpecsFinder::class);
        $changedSpecsFinder->expects(self::once())
            ->method('findChangedSpecs')
            ->with('main', 'specs', true)
            ->willReturn([]);

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--specs-dir' => 'specs', '--include-untracked' => true]);

        self::assertSame(0, $tester->getStatusCode());
    }

    #[Test]
    public function itPassesFalseForIncludeUntrackedByDefault(): void
    {
        $changedTestFinder = static::createMock(ChangedTestFinder::class);
        $changedTestFinder->expects(self::once())
            ->method('findChangedTests')
            ->with('main', false)
            ->willReturn([]);

        $changedSpecsFinder = static::createMock(ChangedSpecsFinder::class);
        $changedSpecsFinder->expects(self::once())
            ->method('findChangedSpecs')
            ->with('main', 'specs', false)
            ->willReturn([]);

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, $changedSpecsFinder, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--specs-dir' => 'specs']);

        self::assertSame(0, $tester->getStatusCode());
    }

    #[Test]
    public function itReturnsErrorWhenConfigFileNotFound(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([]);

        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, null, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--config' => '/non/existent/config.php']);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Config file not found', $tester->getDisplay());
    }

    #[Test]
    public function itUsesConfigBranchWhenCliNotProvided(): void
    {
        $changedTestFinder = static::createMock(ChangedTestFinder::class);
        $changedTestFinder->expects(self::once())
            ->method('findChangedTests')
            ->with('develop', true)
            ->willReturn([]);

        $configPath = __DIR__.'/../Fixtures/Config/valid-config.php';
        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, null, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--config' => $configPath]);

        self::assertSame(0, $tester->getStatusCode());
    }

    #[Test]
    public function itOverridesConfigBranchWithCliOption(): void
    {
        $changedTestFinder = static::createMock(ChangedTestFinder::class);
        $changedTestFinder->expects(self::once())
            ->method('findChangedTests')
            ->with('feature', true)
            ->willReturn([]);

        $configPath = __DIR__.'/../Fixtures/Config/valid-config.php';
        $command = new FindChangedTestsCommand($changedTestFinder, $this->testClassifier, $this->configLoader, null, $this->formatters);
        $tester = new CommandTester($command);
        $tester->execute(['--config' => $configPath, '--branch' => 'feature']);

        self::assertSame(0, $tester->getStatusCode());
    }
}
