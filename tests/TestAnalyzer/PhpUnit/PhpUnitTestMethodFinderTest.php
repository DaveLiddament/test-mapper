<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\TestAnalyzer\PhpUnit;

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

        self::assertCount(1, $result);
        self::assertSame('itHasDataProvider', $result[0]->methodName);

        // The docblock starts at line 13, method ends at line 21
        self::assertSame(13, $result[0]->startLine);
        self::assertSame(21, $result[0]->endLine);
    }

    #[Test]
    public function itRecordsCorrectFilePath(): void
    {
        $path = $this->fixturePath('SimpleTestClass.php');
        $result = $this->finder->findTestMethods($path);

        self::assertSame($path, $result[0]->filePath);
    }

    private function fixturePath(string $filename): string
    {
        return __DIR__.'/../../Fixtures/TestAnalyzer/'.$filename;
    }
}
