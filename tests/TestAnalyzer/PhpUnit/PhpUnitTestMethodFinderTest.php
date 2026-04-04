<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\TestAnalyzer\PhpUnit;

use DaveLiddament\TestMapper\Exception\ParseException;
use DaveLiddament\TestMapper\TestAnalyzer\PhpUnit\PhpUnitTestMethodFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpUnitTestMethodFinder::class)]
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

        self::assertCount(2, $result);
        self::assertSame('DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer\SimpleTestClass', $result[0]->fullyQualifiedClassName);
        self::assertSame('itDoesSomething', $result[0]->methodName);
        self::assertSame('itDoesSomethingElse', $result[1]->methodName);
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

        self::assertCount(2, $result);
        self::assertSame('DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer\FirstTestClass', $result[0]->fullyQualifiedClassName);
        self::assertSame('firstTest', $result[0]->methodName);
        self::assertSame('DaveLiddament\TestMapper\Tests\Fixtures\TestAnalyzer\SecondTestClass', $result[1]->fullyQualifiedClassName);
        self::assertSame('secondTest', $result[1]->methodName);
    }

    #[Test]
    public function itIncludesDocblockAndAttributesInStartLine(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('AttributeTestClass.php'));

        self::assertCount(2, $result);

        // First method: docblock starts at line 13, method ends at line 21
        self::assertSame('itHasDataProvider', $result[0]->methodName);
        self::assertSame(13, $result[0]->startLine);
        self::assertSame(21, $result[0]->endLine);

        // Second method: no docblock, #[Test] attribute starts at line 23
        self::assertSame('itHasNoDocblock', $result[1]->methodName);
        self::assertSame(23, $result[1]->startLine);
        self::assertSame(28, $result[1]->endLine);
    }

    #[Test]
    public function itRecordsCorrectFilePath(): void
    {
        $path = $this->fixturePath('SimpleTestClass.php');
        $result = $this->finder->findTestMethods($path);

        self::assertSame($path, $result[0]->filePath);
    }

    #[Test]
    public function itFindsDependentRangesForDataProvider(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('DataProviderTestClass.php'));

        self::assertCount(2, $result);

        // Test with provider has dependent range pointing to additionProvider
        self::assertSame('itAdds', $result[0]->methodName);
        self::assertCount(1, $result[0]->dependentRanges);
        self::assertSame(26, $result[0]->dependentRanges[0]->startLine);
        self::assertSame(33, $result[0]->dependentRanges[0]->endLine);

        // Test without provider has no dependent ranges
        self::assertSame('itHasNoProvider', $result[1]->methodName);
        self::assertSame([], $result[1]->dependentRanges);
    }

    #[Test]
    public function itFindsDependentRangesForSharedDataProvider(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('AttributeTestClass.php'));

        self::assertCount(2, $result);

        // Both tests share the same data provider
        self::assertSame('itHasDataProvider', $result[0]->methodName);
        self::assertCount(1, $result[0]->dependentRanges);
        self::assertSame(30, $result[0]->dependentRanges[0]->startLine);
        self::assertSame(37, $result[0]->dependentRanges[0]->endLine);

        self::assertSame('itHasNoDocblock', $result[1]->methodName);
        self::assertCount(1, $result[1]->dependentRanges);
        self::assertSame(30, $result[1]->dependentRanges[0]->startLine);
        self::assertSame(37, $result[1]->dependentRanges[0]->endLine);
    }

    #[Test]
    public function itFindsDependentRangesForMultipleDataProviders(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('MultipleDataProviderTestClass.php'));

        self::assertCount(1, $result);
        self::assertSame('itHasMultipleProviders', $result[0]->methodName);
        self::assertCount(2, $result[0]->dependentRanges);
    }

    #[Test]
    public function itReturnsEmptyDependentRangesWhenNoDataProvider(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('SimpleTestClass.php'));

        self::assertCount(2, $result);
        self::assertSame([], $result[0]->dependentRanges);
        self::assertSame([], $result[1]->dependentRanges);
    }

    #[Test]
    public function itExtractsSingleTicketId(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('TicketTestClass.php'));

        self::assertCount(3, $result);
        self::assertSame('itHasSingleTicket', $result[0]->methodName);
        self::assertSame(['JIRA-123'], $result[0]->ticketIds);
    }

    #[Test]
    public function itExtractsMultipleTicketIds(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('TicketTestClass.php'));

        self::assertSame('itHasMultipleTickets', $result[1]->methodName);
        self::assertSame(['JIRA-456', 'JIRA-789'], $result[1]->ticketIds);
    }

    #[Test]
    public function itReturnsEmptyTicketIdsWhenNoTicketAttribute(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('TicketTestClass.php'));

        self::assertSame('itHasNoTicket', $result[2]->methodName);
        self::assertSame([], $result[2]->ticketIds);
    }

    #[Test]
    public function itInheritsClassLevelTicket(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('ClassLevelTicketTestClass.php'));

        self::assertSame('itInheritsClassTicket', $result[0]->methodName);
        self::assertSame(['auth/login'], $result[0]->ticketIds);
    }

    #[Test]
    public function itMergesClassAndMethodLevelTickets(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('ClassLevelTicketTestClass.php'));

        self::assertSame('itMergesClassAndMethodTickets', $result[1]->methodName);
        self::assertSame(['auth/login', 'auth/session'], $result[1]->ticketIds);
    }

    #[Test]
    public function itInheritsMultipleClassLevelTickets(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('MultipleClassLevelTicketsTestClass.php'));

        self::assertCount(1, $result);
        self::assertSame(['auth/login', 'auth/session'], $result[0]->ticketIds);
    }

    #[Test]
    public function itInheritsClassLevelTicketWithDataProvider(): void
    {
        $result = $this->finder->findTestMethods($this->fixturePath('ClassLevelTicketTestClass.php'));

        self::assertSame('itWorksWithDataProvider', $result[2]->methodName);
        self::assertSame(['auth/login'], $result[2]->ticketIds);
        self::assertCount(1, $result[2]->dependentRanges);
    }

    private function fixturePath(string $filename): string
    {
        return __DIR__.'/../../Fixtures/TestAnalyzer/'.$filename;
    }
}
