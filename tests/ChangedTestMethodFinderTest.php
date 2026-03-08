<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests;

use DaveLiddament\TestMapper\ChangedTestMethodFinder;
use DaveLiddament\TestMapper\Diff\DiffProvider;
use DaveLiddament\TestMapper\Model\ChangedFile;
use DaveLiddament\TestMapper\Model\ChangedLineRange;
use DaveLiddament\TestMapper\Model\TestMethod;
use DaveLiddament\TestMapper\TestAnalyzer\TestMethodFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChangedTestMethodFinder::class)]
final class ChangedTestMethodFinderTest extends TestCase
{
    #[Test]
    public function itFindsChangedTestMethods(): void
    {
        $diffProvider = static::createStub(DiffProvider::class);
        $diffProvider->method('getChangedFiles')->willReturn([
            new ChangedFile('tests/FooTest.php', [
                new ChangedLineRange(10, 5), // lines 10-14
            ]),
        ]);

        $testMethodFinder = static::createStub(TestMethodFinder::class);
        $testMethodFinder->method('findTestMethods')->willReturn([
            new TestMethod('App\\Tests\\FooTest', 'it_works', 10, 20, 'tests/FooTest.php'),
            new TestMethod('App\\Tests\\FooTest', 'it_does_not_overlap', 25, 30, 'tests/FooTest.php'),
        ]);

        $finder = new ChangedTestMethodFinder($diffProvider, $testMethodFinder);
        $result = $finder->findChangedTests('main');

        self::assertCount(1, $result);
        self::assertSame('App\\Tests\\FooTest::it_works', $result[0]->getFullyQualifiedName());
    }

    #[Test]
    public function itSkipsNonPhpFiles(): void
    {
        $diffProvider = static::createStub(DiffProvider::class);
        $diffProvider->method('getChangedFiles')->willReturn([
            new ChangedFile('README.md', [
                new ChangedLineRange(1, 10),
            ]),
        ]);

        $testMethodFinder = static::createMock(TestMethodFinder::class);
        $testMethodFinder->expects(self::never())->method('findTestMethods');

        $finder = new ChangedTestMethodFinder($diffProvider, $testMethodFinder);
        $result = $finder->findChangedTests('main');

        self::assertSame([], $result);
    }

    #[Test]
    public function itReturnsEmptyWhenNoOverlap(): void
    {
        $diffProvider = static::createStub(DiffProvider::class);
        $diffProvider->method('getChangedFiles')->willReturn([
            new ChangedFile('tests/FooTest.php', [
                new ChangedLineRange(1, 3), // lines 1-3
            ]),
        ]);

        $testMethodFinder = static::createStub(TestMethodFinder::class);
        $testMethodFinder->method('findTestMethods')->willReturn([
            new TestMethod('App\\Tests\\FooTest', 'it_works', 10, 20, 'tests/FooTest.php'),
        ]);

        $finder = new ChangedTestMethodFinder($diffProvider, $testMethodFinder);
        $result = $finder->findChangedTests('main');

        self::assertSame([], $result);
    }

    #[Test]
    public function itReturnsEmptyWhenNoChangedFiles(): void
    {
        $diffProvider = static::createStub(DiffProvider::class);
        $diffProvider->method('getChangedFiles')->willReturn([]);

        $testMethodFinder = static::createStub(TestMethodFinder::class);

        $finder = new ChangedTestMethodFinder($diffProvider, $testMethodFinder);
        $result = $finder->findChangedTests('main');

        self::assertSame([], $result);
    }
}
