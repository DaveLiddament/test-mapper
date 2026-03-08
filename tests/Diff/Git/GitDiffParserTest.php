<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Diff\Git;

use DaveLiddament\TestMapper\Diff\Git\GitDiffParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitDiffParser::class)]
final class GitDiffParserTest extends TestCase
{
    private GitDiffParser $parser;

    protected function setUp(): void
    {
        $this->parser = new GitDiffParser();
    }

    #[Test]
    public function itParsesEmptyDiff(): void
    {
        $result = $this->parser->parse('');
        self::assertSame([], $result);
    }

    #[Test]
    public function itParsesSimpleAddition(): void
    {
        $diff = $this->loadFixture('simple_addition.diff');
        $result = $this->parser->parse($diff);

        self::assertCount(1, $result);
        self::assertSame('src/Foo.php', $result[0]->filePath);
        self::assertCount(1, $result[0]->changedLineRanges);
        self::assertSame(11, $result[0]->changedLineRanges[0]->startLine);
        self::assertSame(3, $result[0]->changedLineRanges[0]->lineCount);
    }

    #[Test]
    public function itSkipsDeletionOnlyHunks(): void
    {
        $diff = $this->loadFixture('deletion_only.diff');
        $result = $this->parser->parse($diff);

        self::assertCount(1, $result);
        self::assertSame('src/Foo.php', $result[0]->filePath);
        self::assertSame([], $result[0]->changedLineRanges);
    }

    #[Test]
    public function itParsesNewFile(): void
    {
        $diff = $this->loadFixture('new_file.diff');
        $result = $this->parser->parse($diff);

        self::assertCount(1, $result);
        self::assertSame('src/NewClass.php', $result[0]->filePath);
        self::assertCount(1, $result[0]->changedLineRanges);
        self::assertSame(1, $result[0]->changedLineRanges[0]->startLine);
        self::assertSame(10, $result[0]->changedLineRanges[0]->lineCount);
    }

    #[Test]
    public function itExcludesDeletedFiles(): void
    {
        $diff = $this->loadFixture('deleted_file.diff');
        $result = $this->parser->parse($diff);

        self::assertSame([], $result);
    }

    #[Test]
    public function itParsesMultipleHunks(): void
    {
        $diff = $this->loadFixture('multiple_hunks.diff');
        $result = $this->parser->parse($diff);

        self::assertCount(1, $result);
        self::assertSame('src/Foo.php', $result[0]->filePath);
        self::assertCount(2, $result[0]->changedLineRanges);
        self::assertSame(5, $result[0]->changedLineRanges[0]->startLine);
        self::assertSame(3, $result[0]->changedLineRanges[0]->lineCount);
        self::assertSame(22, $result[0]->changedLineRanges[1]->startLine);
        self::assertSame(4, $result[0]->changedLineRanges[1]->lineCount);
    }

    #[Test]
    public function itParsesMultipleFiles(): void
    {
        $diff = $this->loadFixture('multiple_files.diff');
        $result = $this->parser->parse($diff);

        self::assertCount(2, $result);
        self::assertSame('src/Foo.php', $result[0]->filePath);
        self::assertSame('src/Bar.php', $result[1]->filePath);
    }

    #[Test]
    public function itHandlesImplicitCountOfOne(): void
    {
        $diff = $this->loadFixture('multiple_files.diff');
        $result = $this->parser->parse($diff);

        // The second file has @@ -15 +15 @@ (implicit count=1)
        self::assertCount(1, $result[1]->changedLineRanges);
        self::assertSame(15, $result[1]->changedLineRanges[0]->startLine);
        self::assertSame(1, $result[1]->changedLineRanges[0]->lineCount);
    }

    private function loadFixture(string $filename): string
    {
        $path = __DIR__.'/../../Fixtures/Diff/'.$filename;
        $contents = file_get_contents($path);
        self::assertNotFalse($contents);

        return $contents;
    }
}
