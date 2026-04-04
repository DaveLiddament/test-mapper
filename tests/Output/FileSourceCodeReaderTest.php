<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Output;

use DaveLiddament\TestMapper\Output\FileSourceCodeReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileSourceCodeReader::class)]
final class FileSourceCodeReaderTest extends TestCase
{
    private FileSourceCodeReader $reader;

    protected function setUp(): void
    {
        $this->reader = new FileSourceCodeReader();
    }

    #[Test]
    public function itReadsCorrectLineRange(): void
    {
        $path = $this->fixturePath('SampleTest.php');

        $result = $this->reader->readLines($path, 9, 13);

        self::assertStringContainsString('itDoesSomething', $result);
        self::assertStringContainsString('$a = 1;', $result);
        self::assertStringContainsString('$b = 2;', $result);
    }

    #[Test]
    public function itReadsSingleLine(): void
    {
        $path = $this->fixturePath('SampleTest.php');

        $result = $this->reader->readLines($path, 9, 9);

        self::assertStringContainsString('itDoesSomething', $result);
        self::assertStringNotContainsString('$a = 1;', $result);
    }

    #[Test]
    public function itReadsEntireFile(): void
    {
        $path = $this->fixturePath('SampleTest.php');

        $result = $this->reader->readFile($path);

        self::assertStringContainsString('<?php', $result);
        self::assertStringContainsString('itDoesSomething', $result);
        self::assertStringContainsString('dataProvider', $result);
    }

    #[Test]
    public function itReturnsEmptyStringForNonExistentFileReadLines(): void
    {
        $result = $this->reader->readLines('/non/existent/file.php', 1, 5);

        self::assertSame('', $result);
    }

    #[Test]
    public function itReturnsEmptyStringForNonExistentFileReadFile(): void
    {
        $result = $this->reader->readFile('/non/existent/file.php');

        self::assertSame('', $result);
    }

    private function fixturePath(string $filename): string
    {
        return __DIR__.'/../Fixtures/Output/SourceCode/'.$filename;
    }
}
