<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Command;

use DaveLiddament\TestMapper\Command\SpecReviewerCommand;
use DaveLiddament\TestMapper\Config\ConfigLoader;
use DaveLiddament\TestMapper\Model\LineRange;
use DaveLiddament\TestMapper\Model\TestMethod;
use DaveLiddament\TestMapper\Output\FileSourceCodeReader;
use DaveLiddament\TestMapper\TestAnalyzer\TestMethodFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(SpecReviewerCommand::class)]
#[Ticket('024-spec-reviewer-command')]
#[Ticket('022-ai-review-markdown-format')]
#[Ticket('017-cli-option-precedence')]
#[Ticket('025-cli-output-redirection')]
final class SpecReviewerCommandTest extends TestCase
{
    private string $specsDir;

    protected function setUp(): void
    {
        $this->specsDir = __DIR__.'/../Fixtures/Output/Specs';
    }

    #[Test]
    public function itReturnsErrorWhenNoSpecsDirProvided(): void
    {
        $command = $this->createCommand([]);
        $tester = new CommandTester($command);
        $tester->execute(['specs' => ['auth/login']]);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('--specs-dir option is required', $tester->getDisplay());
    }

    #[Test]
    public function itReturnsErrorWhenSpecsDirDoesNotExist(): void
    {
        $command = $this->createCommand([]);
        $tester = new CommandTester($command);
        $tester->execute([
            'specs' => ['auth/login'],
            '--specs-dir' => '/non/existent/specs',
        ]);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Specs directory not found', $tester->getDisplay());
    }

    #[Test]
    public function itReturnsErrorWhenNoSpecNamesProvided(): void
    {
        $command = $this->createCommand([]);
        $tester = new CommandTester($command);
        $tester->execute(['--specs-dir' => $this->specsDir]);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('No spec names provided', $tester->getDisplay());
    }

    #[Test]
    public function itOutputsMarkdownForMatchingTests(): void
    {
        $testMethod = new TestMethod(
            'App\\Tests\\LoginTest',
            'it_validates',
            10,
            15,
            'tests/LoginTest.php',
            [],
            ['auth/login'],
        );

        $command = $this->createCommand([$testMethod]);
        $tester = new CommandTester($command);
        $tester->execute([
            'specs' => ['auth/login'],
            '--specs-dir' => $this->specsDir,
        ]);

        self::assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();

        self::assertStringContainsString('# Changes to Review', $display);
        self::assertStringContainsString('## Contents', $display);
        self::assertStringContainsString('## Specs', $display);
        self::assertStringContainsString('### auth/login', $display);
        self::assertStringContainsString('The login endpoint accepts a username and password.', $display);
        self::assertStringContainsString('## Tests', $display);
        self::assertStringContainsString('`tests/LoginTest.php`', $display);
        self::assertStringContainsString('```php', $display);
    }

    #[Test]
    public function itSortsSpecsAlphabetically(): void
    {
        $testA = new TestMethod('App\\Tests\\SessionTest', 'it_expires', 10, 15, 'tests/SessionTest.php', [], ['auth/session']);
        $testB = new TestMethod('App\\Tests\\LoginTest', 'it_validates', 10, 15, 'tests/LoginTest.php', [], ['auth/login']);

        $command = $this->createCommand([$testA, $testB]);
        $tester = new CommandTester($command);
        $tester->execute([
            'specs' => ['auth/session', 'auth/login'],
            '--specs-dir' => $this->specsDir,
        ]);

        $display = $tester->getDisplay();
        $loginPos = strpos($display, '### auth/login');
        $sessionPos = strpos($display, '### auth/session');
        self::assertNotFalse($loginPos);
        self::assertNotFalse($sessionPos);
        self::assertLessThan($sessionPos, $loginPos);
    }

    #[Test]
    public function itOutputsEmptySectionsWhenNoMatchingTests(): void
    {
        $command = $this->createCommand([]);
        $tester = new CommandTester($command);
        $tester->execute([
            'specs' => ['auth/login'],
            '--specs-dir' => $this->specsDir,
        ]);

        self::assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        self::assertStringContainsString('# Changes to Review', $display);
        self::assertStringContainsString('## Tests', $display);
        self::assertStringNotContainsString('```php', $display);
    }

    #[Test]
    public function itIncludesDependentRangesInOutput(): void
    {
        $testMethod = new TestMethod(
            'App\\Tests\\LoginTest',
            'it_validates',
            20,
            25,
            __DIR__.'/../Fixtures/Output/SourceCode/SampleTest.php',
            [new LineRange(15, 21)],
            ['auth/login'],
        );

        $command = $this->createCommand([$testMethod], useRealReader: true);
        $tester = new CommandTester($command);
        $tester->execute([
            'specs' => ['auth/login'],
            '--specs-dir' => $this->specsDir,
        ]);

        $display = $tester->getDisplay();
        self::assertStringContainsString('dataProvider', $display);
    }

    #[Test]
    public function itOmitsSpecsSectionWhenNoSpecsFlagIsSet(): void
    {
        $testMethod = new TestMethod(
            'App\\Tests\\LoginTest',
            'it_validates',
            10,
            15,
            'tests/LoginTest.php',
            [],
            ['auth/login'],
        );

        $command = $this->createCommand([$testMethod]);
        $tester = new CommandTester($command);
        $tester->execute([
            'specs' => ['auth/login'],
            '--specs-dir' => $this->specsDir,
            '--no-specs' => true,
        ]);

        $display = $tester->getDisplay();
        self::assertStringContainsString('# Changes to Review', $display);
        self::assertStringContainsString('## Tests', $display);
        self::assertStringNotContainsString('## Specs', $display);
        self::assertStringNotContainsString('The login endpoint', $display);
    }

    #[Test]
    public function itIncludesSpecLinksInTocButNotSpecSection(): void
    {
        $testMethod = new TestMethod(
            'App\\Tests\\LoginTest',
            'it_validates',
            10,
            15,
            'tests/LoginTest.php',
            [],
            ['auth/login'],
        );

        $command = $this->createCommand([$testMethod]);
        $tester = new CommandTester($command);
        $tester->execute([
            'specs' => ['auth/login'],
            '--specs-dir' => $this->specsDir,
            '--no-specs' => true,
        ]);

        $display = $tester->getDisplay();
        $contentsSection = substr($display, 0, (int) strpos($display, '---'));

        // TOC should NOT have a Specs subsection when --no-specs is set
        self::assertStringNotContainsString('### Specs', $contentsSection);
        // TOC should still have Tests subsection
        self::assertStringContainsString('### Tests', $contentsSection);
    }

    #[Test]
    public function itReturnsErrorWhenConfigFileNotFound(): void
    {
        $command = $this->createCommand([]);
        $tester = new CommandTester($command);
        $tester->execute([
            'specs' => ['auth/login'],
            '--config' => '/non/existent/config.php',
            '--specs-dir' => $this->specsDir,
        ]);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Config file not found', $tester->getDisplay());
    }

    #[Test]
    public function itUsesSpecsDirFromConfig(): void
    {
        $testMethod = new TestMethod(
            'App\\Tests\\LoginTest',
            'it_validates',
            10,
            15,
            'tests/LoginTest.php',
            [],
            ['auth/login'],
        );

        $configPath = __DIR__.'/../Fixtures/Config/real-specs-dir-config.php';
        $command = $this->createCommand([$testMethod]);
        $tester = new CommandTester($command);
        $tester->execute([
            'specs' => ['auth/login'],
            '--config' => $configPath,
        ]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('# Changes to Review', $tester->getDisplay());
    }

    #[Test]
    public function itUsesNoSpecsFromConfig(): void
    {
        $testMethod = new TestMethod(
            'App\\Tests\\LoginTest',
            'it_validates',
            10,
            15,
            'tests/LoginTest.php',
            [],
            ['auth/login'],
        );

        $configPath = __DIR__.'/../Fixtures/Config/no-specs-config.php';
        $command = $this->createCommand([$testMethod]);
        $tester = new CommandTester($command);
        $tester->execute([
            'specs' => ['auth/login'],
            '--config' => $configPath,
        ]);

        $display = $tester->getDisplay();
        self::assertStringNotContainsString('## Specs', $display);
    }

    #[Test]
    public function itAcceptsTestDirCliOption(): void
    {
        $testMethod = new TestMethod(
            'App\\Tests\\LoginTest',
            'it_validates',
            10,
            15,
            'tests/LoginTest.php',
            [],
            ['auth/login'],
        );

        $command = $this->createCommand([$testMethod]);
        $tester = new CommandTester($command);
        $tester->execute([
            'specs' => ['auth/login'],
            '--specs-dir' => $this->specsDir,
            '--test-dir' => ['tests'],
        ]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('# Changes to Review', $tester->getDisplay());
    }

    #[Test]
    public function itAcceptsExcludeTestDirCliOption(): void
    {
        $testMethod = new TestMethod(
            'App\\Tests\\LoginTest',
            'it_validates',
            10,
            15,
            'tests/LoginTest.php',
            [],
            ['auth/login'],
        );

        $command = $this->createCommand([$testMethod]);
        $tester = new CommandTester($command);
        $tester->execute([
            'specs' => ['auth/login'],
            '--specs-dir' => $this->specsDir,
            '--exclude-test-dir' => ['tests/Fixtures'],
        ]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('# Changes to Review', $tester->getDisplay());
    }

    #[Test]
    public function itOverridesConfigSpecsDirWithCliOption(): void
    {
        $testMethod = new TestMethod(
            'App\\Tests\\LoginTest',
            'it_validates',
            10,
            15,
            'tests/LoginTest.php',
            [],
            ['auth/login'],
        );

        $configPath = __DIR__.'/../Fixtures/Config/specs-dir-only-config.php';
        $command = $this->createCommand([$testMethod]);
        $tester = new CommandTester($command);
        $tester->execute([
            'specs' => ['auth/login'],
            '--config' => $configPath,
            '--specs-dir' => $this->specsDir,
        ]);

        $display = $tester->getDisplay();
        self::assertStringContainsString('The login endpoint accepts a username and password.', $display);
    }

    #[Test]
    public function itWritesOutputToFile(): void
    {
        $testMethod = new TestMethod(
            'App\\Tests\\LoginTest',
            'it_validates',
            10,
            15,
            'tests/LoginTest.php',
            [],
            ['auth/login'],
        );

        $outputPath = tempnam(sys_get_temp_dir(), 'spec-reviewer-');
        self::assertNotFalse($outputPath);

        try {
            $command = $this->createCommand([$testMethod]);
            $tester = new CommandTester($command);
            $tester->execute([
                'specs' => ['auth/login'],
                '--specs-dir' => $this->specsDir,
                '--output' => $outputPath,
            ]);

            self::assertSame(0, $tester->getStatusCode());
            self::assertSame('', $tester->getDisplay());

            $fileContents = file_get_contents($outputPath);
            self::assertNotFalse($fileContents);
            self::assertStringContainsString('# Changes to Review', $fileContents);
            self::assertStringContainsString('auth/login', $fileContents);
        } finally {
            @unlink($outputPath);
        }
    }

    /**
     * @param list<TestMethod> $testMethods
     */
    private function createCommand(array $testMethods, bool $useRealReader = false): SpecReviewerCommand
    {
        $testMethodFinder = static::createStub(TestMethodFinder::class);
        $testMethodFinder->method('findTestMethods')->willReturn($testMethods);

        $sourceCodeReader = $useRealReader
            ? new FileSourceCodeReader()
            : new FileSourceCodeReader();

        return new SpecReviewerCommand($testMethodFinder, $sourceCodeReader, new ConfigLoader());
    }
}
