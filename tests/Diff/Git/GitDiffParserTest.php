<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Diff\Git;

use DaveLiddament\TestMapper\Diff\Git\GitDiffParser;
use DaveLiddament\TestMapper\Tests\Helper\DiffFixtureGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitDiffParser::class)]
#[Ticket('009-unified-diff-parser')]
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
        $diff = DiffFixtureGenerator::generate(
            __DIR__.'/../../Fixtures/Diff/SimpleAddition/before.php',
            __DIR__.'/../../Fixtures/Diff/SimpleAddition/after.php',
            'src/Foo.php',
        );
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
        $diff = DiffFixtureGenerator::generate(
            __DIR__.'/../../Fixtures/Diff/DeletionOnly/before.php',
            __DIR__.'/../../Fixtures/Diff/DeletionOnly/after.php',
            'src/Foo.php',
        );
        $result = $this->parser->parse($diff);

        self::assertCount(1, $result);
        self::assertSame('src/Foo.php', $result[0]->filePath);
        self::assertSame([], $result[0]->changedLineRanges);
    }

    #[Test]
    public function itParsesNewFile(): void
    {
        $diff = DiffFixtureGenerator::generate(
            '/dev/null',
            __DIR__.'/../../Fixtures/Diff/NewFile/after.php',
            'src/NewClass.php',
        );
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
        $diff = DiffFixtureGenerator::generate(
            __DIR__.'/../../Fixtures/Diff/DeletedFile/before.php',
            '/dev/null',
            'src/OldClass.php',
        );
        $result = $this->parser->parse($diff);

        self::assertSame([], $result);
    }

    #[Test]
    public function itParsesMultipleHunks(): void
    {
        $diff = DiffFixtureGenerator::generate(
            __DIR__.'/../../Fixtures/Diff/MultipleHunks/before.php',
            __DIR__.'/../../Fixtures/Diff/MultipleHunks/after.php',
            'src/Foo.php',
        );
        $result = $this->parser->parse($diff);

        self::assertCount(1, $result);
        self::assertSame('src/Foo.php', $result[0]->filePath);
        self::assertCount(2, $result[0]->changedLineRanges);
        self::assertSame(9, $result[0]->changedLineRanges[0]->startLine);
        self::assertSame(3, $result[0]->changedLineRanges[0]->lineCount);
        self::assertSame(23, $result[0]->changedLineRanges[1]->startLine);
        self::assertSame(5, $result[0]->changedLineRanges[1]->lineCount);
    }

    #[Test]
    public function itParsesMultipleFiles(): void
    {
        $diff = DiffFixtureGenerator::generate(
            __DIR__.'/../../Fixtures/Diff/MultipleFiles/Foo/before.php',
            __DIR__.'/../../Fixtures/Diff/MultipleFiles/Foo/after.php',
            'src/Foo.php',
        ).DiffFixtureGenerator::generate(
            __DIR__.'/../../Fixtures/Diff/MultipleFiles/Bar/before.php',
            __DIR__.'/../../Fixtures/Diff/MultipleFiles/Bar/after.php',
            'src/Bar.php',
        );
        $result = $this->parser->parse($diff);

        self::assertCount(2, $result);
        self::assertSame('src/Foo.php', $result[0]->filePath);
        self::assertSame('src/Bar.php', $result[1]->filePath);
    }

    #[Test]
    public function itIgnoresMalformedHunkHeaders(): void
    {
        $diff = implode("\n", [
            'diff --git a/src/Foo.php b/src/Foo.php',
            '--- a/src/Foo.php',
            '+++ b/src/Foo.php',
            '@@ malformed header @@',
        ]);
        $result = $this->parser->parse($diff);

        self::assertCount(1, $result);
        self::assertSame('src/Foo.php', $result[0]->filePath);
        self::assertSame([], $result[0]->changedLineRanges);
    }

    #[Test]
    public function itHandlesImplicitCountOfOne(): void
    {
        $diff = DiffFixtureGenerator::generate(
            __DIR__.'/../../Fixtures/Diff/MultipleFiles/Bar/before.php',
            __DIR__.'/../../Fixtures/Diff/MultipleFiles/Bar/after.php',
            'src/Bar.php',
        );
        $result = $this->parser->parse($diff);

        // The hunk @@ -13 +13 @@ has implicit count=1
        self::assertCount(1, $result[0]->changedLineRanges);
        self::assertSame(13, $result[0]->changedLineRanges[0]->startLine);
        self::assertSame(1, $result[0]->changedLineRanges[0]->lineCount);
    }
}
