<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Specs;

use DaveLiddament\TestMapper\Model\ChangedSpecFile;
use DaveLiddament\TestMapper\Model\FileChangeType;
use DaveLiddament\TestMapper\Specs\NameStatusDiffParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NameStatusDiffParser::class)]
final class NameStatusDiffParserTest extends TestCase
{
    private NameStatusDiffParser $parser;

    protected function setUp(): void
    {
        $this->parser = new NameStatusDiffParser();
    }

    #[Test]
    public function itReturnsEmptyArrayForEmptyOutput(): void
    {
        $result = $this->parser->parse('', 'specs');
        self::assertSame([], $result);
    }

    #[Test]
    public function itParsesAddedFile(): void
    {
        $result = $this->parser->parse("A\tspecs/auth/login.md", 'specs');

        self::assertCount(1, $result);
        self::assertChangedSpecFile(FileChangeType::Added, 'auth/login', $result[0]);
    }

    #[Test]
    public function itParsesModifiedFile(): void
    {
        $result = $this->parser->parse("M\tspecs/auth/login.md", 'specs');

        self::assertCount(1, $result);
        self::assertChangedSpecFile(FileChangeType::Modified, 'auth/login', $result[0]);
    }

    #[Test]
    public function itParsesDeletedFile(): void
    {
        $result = $this->parser->parse("D\tspecs/auth/login.md", 'specs');

        self::assertCount(1, $result);
        self::assertChangedSpecFile(FileChangeType::Deleted, 'auth/login', $result[0]);
    }

    #[Test]
    public function itParsesRenameAsDeletedAndAdded(): void
    {
        $result = $this->parser->parse("R100\tspecs/old/path.md\tspecs/new/path.md", 'specs');

        self::assertCount(2, $result);
        self::assertChangedSpecFile(FileChangeType::Deleted, 'old/path', $result[0]);
        self::assertChangedSpecFile(FileChangeType::Added, 'new/path', $result[1]);
    }

    #[Test]
    public function itParsesCopyAsAdded(): void
    {
        $result = $this->parser->parse("C100\tspecs/source.md\tspecs/dest.md", 'specs');

        self::assertCount(1, $result);
        self::assertChangedSpecFile(FileChangeType::Added, 'dest', $result[0]);
    }

    #[Test]
    public function itParsesTypeChangeAsModified(): void
    {
        $result = $this->parser->parse("T\tspecs/path.md", 'specs');

        self::assertCount(1, $result);
        self::assertChangedSpecFile(FileChangeType::Modified, 'path', $result[0]);
    }

    #[Test]
    public function itParsesMultipleFiles(): void
    {
        $output = implode("\n", [
            "A\tspecs/new-file.md",
            "M\tspecs/existing.md",
            "D\tspecs/removed.md",
        ]);

        $result = $this->parser->parse($output, 'specs');

        self::assertCount(3, $result);
        self::assertChangedSpecFile(FileChangeType::Added, 'new-file', $result[0]);
        self::assertChangedSpecFile(FileChangeType::Modified, 'existing', $result[1]);
        self::assertChangedSpecFile(FileChangeType::Deleted, 'removed', $result[2]);
    }

    #[Test]
    public function itSkipsUnknownStatusLetters(): void
    {
        $result = $this->parser->parse("X\tspecs/unknown.md", 'specs');

        self::assertSame([], $result);
    }

    #[Test]
    public function itSkipsEmptyAndWhitespaceLines(): void
    {
        $output = "\n  \n\t\nA\tspecs/file.md\n\n";

        $result = $this->parser->parse($output, 'specs');

        self::assertCount(1, $result);
        self::assertChangedSpecFile(FileChangeType::Added, 'file', $result[0]);
    }

    #[Test]
    public function itHandlesNestedDirectories(): void
    {
        $result = $this->parser->parse("M\tspecs/sub/deep/file.md", 'specs');

        self::assertCount(1, $result);
        self::assertChangedSpecFile(FileChangeType::Modified, 'sub/deep/file', $result[0]);
    }

    #[Test]
    public function itStripsTrailingSlashFromPrefix(): void
    {
        $result = $this->parser->parse("A\tspecs/file.md", 'specs/');

        self::assertCount(1, $result);
        self::assertChangedSpecFile(FileChangeType::Added, 'file', $result[0]);
    }

    #[Test]
    public function itKeepsPathWhenPrefixDoesNotMatch(): void
    {
        $result = $this->parser->parse("A\tother/file.md", 'specs');

        self::assertCount(1, $result);
        self::assertChangedSpecFile(FileChangeType::Added, 'other/file', $result[0]);
    }

    #[Test]
    public function itHandlesRenameWithMissingDestination(): void
    {
        $result = $this->parser->parse("R100\tspecs/old.md", 'specs');

        self::assertSame([], $result);
    }

    #[Test]
    public function itSkipsLinesWithNoTabSeparator(): void
    {
        $output = "malformed-line-without-tab\nA\tspecs/file.md";
        $result = $this->parser->parse($output, 'specs');

        self::assertCount(1, $result);
        self::assertChangedSpecFile(FileChangeType::Added, 'file', $result[0]);
    }

    private static function assertChangedSpecFile(
        FileChangeType $expectedType,
        string $expectedPath,
        ChangedSpecFile $actual,
    ): void {
        self::assertSame($expectedType, $actual->changeType);
        self::assertSame($expectedPath, $actual->filePath);
    }
}
