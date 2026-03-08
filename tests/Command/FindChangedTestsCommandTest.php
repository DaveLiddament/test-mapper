<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Command;

use DaveLiddament\TestMapper\ChangedTestFinder;
use DaveLiddament\TestMapper\Command\FindChangedTestsCommand;
use DaveLiddament\TestMapper\Model\ChangedTestMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(FindChangedTestsCommand::class)]
final class FindChangedTestsCommandTest extends TestCase
{
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

        $command = new FindChangedTestsCommand($changedTestFinder);
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

        $command = new FindChangedTestsCommand($changedTestFinder);
        $tester = new CommandTester($command);
        $tester->execute(['--branch' => 'develop']);

        self::assertSame(0, $tester->getStatusCode());
    }

    #[Test]
    public function itOutputsNothingWhenNoChanges(): void
    {
        $changedTestFinder = static::createStub(ChangedTestFinder::class);
        $changedTestFinder->method('findChangedTests')->willReturn([]);

        $command = new FindChangedTestsCommand($changedTestFinder);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertSame('', $tester->getDisplay());
    }
}
