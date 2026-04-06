<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\TestAnalyzer\PhpUnit;

use DaveLiddament\TestMapper\Exception\ParseException;
use DaveLiddament\TestMapper\Model\LineRange;
use DaveLiddament\TestMapper\Model\TestMethod;
use DaveLiddament\TestMapper\TestAnalyzer\PhpUnit\PhpUnitTestMethodFinder;
use DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer\SimpleTestClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpUnitTestMethodFinder::class)]
#[Ticket('011-phpunit-test-method-finder')]
#[Ticket('026-ticket-convention')]
final class PhpUnitTestMethodFinderTest extends TestCase
{
    private PhpUnitTestMethodFinder $finder;

    protected function setUp(): void
    {
        $this->finder = new PhpUnitTestMethodFinder();
    }

    #[Test]
    public function itThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Could not read file: /non/existent/file.php');

        $this->finder->findTestMethods('/non/existent/file.php');
    }

    #[Test]
    public function itFindsMethodsWithTestAttribute(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('SimpleTestClass.php'));

        $this->assertTestMethods([
            new ExpectedTestMethod(fullyQualifiedClassName: SimpleTestClass::class, methodName: 'itDoesSomething'),
            new ExpectedTestMethod(methodName: 'itDoesSomethingElse'),
        ], $result);
    }

    #[Test]
    public function itDoesNotDetectTestPrefixMethodsWithoutAttribute(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('NonTestClass.php'));

        self::assertSame([], $result);
    }

    #[Test]
    public function itReturnsEmptyForClassWithNoTestMethods(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('NoTestMethodsClass.php'));

        self::assertSame([], $result);
    }

    #[Test]
    public function itFindsTestMethodsFromMultipleClasses(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('MultipleClassesFile.php'));

        $this->assertTestMethods([
            new ExpectedTestMethod(fullyQualifiedClassName: 'DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer\FirstTestClass', methodName: 'firstTest'),
            new ExpectedTestMethod(fullyQualifiedClassName: 'DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer\SecondTestClass', methodName: 'secondTest'),
        ], $result);
    }

    #[Test]
    public function itIncludesDocblockAndAttributesInStartLine(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('AttributeTestClass.php'));

        $this->assertTestMethods([
            new ExpectedTestMethod(methodName: 'itHasDataProvider', startLine: 13, endLine: 21),
            new ExpectedTestMethod(methodName: 'itHasNoDocblock', startLine: 23, endLine: 28),
        ], $result);
    }

    #[Test]
    public function itRecordsCorrectFilePath(): void
    {
        $path = $this->fixturePath('SimpleTestClass.php');
        $result = $this->finder->findTestMethods($path);

        $this->assertTestMethods([
            new ExpectedTestMethod(filePath: $path),
            new ExpectedTestMethod(),
        ], $result);
    }

    #[Test]
    public function itFindsDependentRangesForDataProvider(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('DataProviderTestClass.php'));

        $this->assertTestMethods([
            new ExpectedTestMethod(methodName: 'itAdds', dependentRanges: [new LineRange(26, 33)]),
            new ExpectedTestMethod(methodName: 'itHasNoProvider', dependentRanges: []),
        ], $result);
    }

    #[Test]
    public function itFindsDependentRangesForSharedDataProvider(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('AttributeTestClass.php'));

        $this->assertTestMethods([
            new ExpectedTestMethod(methodName: 'itHasDataProvider', dependentRanges: [new LineRange(30, 37)]),
            new ExpectedTestMethod(methodName: 'itHasNoDocblock', dependentRanges: [new LineRange(30, 37)]),
        ], $result);
    }

    #[Test]
    public function itFindsDependentRangesForMultipleDataProviders(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('MultipleDataProviderTestClass.php'));

        $this->assertTestMethods([
            new ExpectedTestMethod(methodName: 'itHasMultipleProviders', dependentRanges: [new LineRange(21, 28), new LineRange(30, 37)]),
        ], $result);
    }

    #[Test]
    public function itReturnsEmptyDependentRangesWhenNoDataProvider(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('SimpleTestClass.php'));

        $this->assertTestMethods([
            new ExpectedTestMethod(dependentRanges: []),
            new ExpectedTestMethod(dependentRanges: []),
        ], $result);
    }

    #[Test]
    public function itExtractsTicketIds(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('TicketTestClass.php'));

        $this->assertTestMethods([
            new ExpectedTestMethod(methodName: 'itHasSingleTicket', ticketIds: ['JIRA-123']),
            new ExpectedTestMethod(methodName: 'itHasMultipleTickets', ticketIds: ['JIRA-456', 'JIRA-789']),
            new ExpectedTestMethod(methodName: 'itHasNoTicket', ticketIds: []),
        ], $result);
    }

    #[Test]
    public function itInheritsClassLevelTicket(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('ClassLevelTicketTestClass.php'));

        $this->assertTestMethods([
            new ExpectedTestMethod(methodName: 'itInheritsClassTicket', ticketIds: ['auth/login']),
            new ExpectedTestMethod(methodName: 'itMergesClassAndMethodTickets', ticketIds: ['auth/login', 'auth/session']),
            new ExpectedTestMethod(methodName: 'itWorksWithDataProvider', ticketIds: ['auth/login'], dependentRanges: [new LineRange(35, 42)]),
        ], $result);
    }

    #[Test]
    public function itInheritsMultipleClassLevelTickets(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('MultipleClassLevelTicketsTestClass.php'));

        $this->assertTestMethods([
            new ExpectedTestMethod(ticketIds: ['auth/login', 'auth/session']),
        ], $result);
    }

    /**
     * @param list<ExpectedTestMethod> $expectedTestMethods
     * @param list<TestMethod> $actualTestMethods
     */
    private function assertTestMethods(array $expectedTestMethods, array $actualTestMethods): void
    {
        self::assertCount(count($expectedTestMethods), $actualTestMethods);

        foreach ($expectedTestMethods as $index => $expected) {
            $actual = $actualTestMethods[$index];

            if (null !== $expected->fullyQualifiedClassName) {
                self::assertSame($expected->fullyQualifiedClassName, $actual->fullyQualifiedClassName, "fullyQualifiedClassName mismatch at index {$index}");
            }

            if (null !== $expected->methodName) {
                self::assertSame($expected->methodName, $actual->methodName, "methodName mismatch at index {$index}");
            }

            if (null !== $expected->startLine) {
                self::assertSame($expected->startLine, $actual->startLine, "startLine mismatch at index {$index}");
            }

            if (null !== $expected->endLine) {
                self::assertSame($expected->endLine, $actual->endLine, "endLine mismatch at index {$index}");
            }

            if (null !== $expected->filePath) {
                self::assertSame($expected->filePath, $actual->filePath, "filePath mismatch at index {$index}");
            }

            if (null !== $expected->dependentRanges) {
                self::assertEquals($expected->dependentRanges, $actual->dependentRanges, "dependentRanges mismatch at index {$index}");
            }

            if (null !== $expected->ticketIds) {
                self::assertSame($expected->ticketIds, $actual->ticketIds, "ticketIds mismatch at index {$index}");
            }
        }
    }

    private function fixturePath(string $filename): string
    {
        return __DIR__.'/../../Fixtures/TestAnalyzer/'.$filename;
    }
}
