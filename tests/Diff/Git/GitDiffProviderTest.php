<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Diff\Git;

use DaveLiddament\TestMapper\Diff\Git\GitDiffParser;
use DaveLiddament\TestMapper\Diff\Git\GitDiffProvider;
use DaveLiddament\TestMapper\Exception\DiffException;
use DaveLiddament\TestMapper\Model\ChangedFile;
use DaveLiddament\TestMapper\Model\ChangedLineRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitDiffProvider::class)]
#[Ticket('008-git-diff-provider')]
final class GitDiffProviderTest extends TestCase
{
    #[Test]
    public function itReturnsChangedFilesFromGit(): void
    {
        $provider = new GitDiffProvider(
            dirname(__DIR__, 3),
            new GitDiffParser(),
        );

        // Compare current state against HEAD — should not throw
        $result = $provider->getChangedFiles('HEAD', false);
        self::assertGreaterThanOrEqual(0, count($result));
    }

    #[Test]
    public function itIncludesUntrackedFiles(): void
    {
        $repoRoot = dirname(__DIR__, 3);
        $untrackedFileA = $repoRoot.'/untracked-test-fixture-a.php';
        $untrackedFileB = $repoRoot.'/untracked-test-fixture-b.php';
        file_put_contents($untrackedFileA, "<?php\n// test\n");
        file_put_contents($untrackedFileB, "<?php\n// test\n");

        try {
            $provider = new GitDiffProvider($repoRoot, new GitDiffParser());
            $result = $provider->getChangedFiles('HEAD', true);

            $untrackedPaths = array_map(
                static fn (ChangedFile $f): string => $f->filePath,
                $result,
            );
            self::assertContains('untracked-test-fixture-a.php', $untrackedPaths);
            self::assertContains('untracked-test-fixture-b.php', $untrackedPaths);

            $untrackedEntry = array_values(array_filter(
                $result,
                static fn (ChangedFile $f): bool => 'untracked-test-fixture-a.php' === $f->filePath,
            ))[0];
            self::assertCount(1, $untrackedEntry->changedLineRanges);
            self::assertEquals(new ChangedLineRange(1, \PHP_INT_MAX), $untrackedEntry->changedLineRanges[0]);
        } finally {
            unlink($untrackedFileA);
            unlink($untrackedFileB);
        }
    }

    #[Test]
    public function itThrowsOnInvalidBranch(): void
    {
        $provider = new GitDiffProvider(
            dirname(__DIR__, 3),
            new GitDiffParser(),
        );

        $this->expectException(DiffException::class);
        $provider->getChangedFiles('non-existent-branch-xyz-123', false);
    }
}
