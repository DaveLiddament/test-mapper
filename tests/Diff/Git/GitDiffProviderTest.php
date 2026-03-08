<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Diff\Git;

use DaveLiddament\TestMapper\Diff\Git\GitDiffParser;
use DaveLiddament\TestMapper\Diff\Git\GitDiffProvider;
use DaveLiddament\TestMapper\Exception\DiffException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitDiffProvider::class)]
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
        $result = $provider->getChangedFiles('HEAD');
        self::assertGreaterThanOrEqual(0, count($result));
    }

    #[Test]
    public function itThrowsOnInvalidBranch(): void
    {
        $provider = new GitDiffProvider(
            dirname(__DIR__, 3),
            new GitDiffParser(),
        );

        $this->expectException(DiffException::class);
        $provider->getChangedFiles('non-existent-branch-xyz-123');
    }
}
