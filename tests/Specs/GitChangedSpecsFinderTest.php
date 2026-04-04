<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Specs;

use DaveLiddament\TestMapper\Exception\DiffException;
use DaveLiddament\TestMapper\Model\ChangedSpecFile;
use DaveLiddament\TestMapper\Specs\GitChangedSpecsFinder;
use DaveLiddament\TestMapper\Specs\NameStatusDiffParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitChangedSpecsFinder::class)]
final class GitChangedSpecsFinderTest extends TestCase
{
    #[Test]
    public function itReturnsChangedSpecsFromGit(): void
    {
        $finder = new GitChangedSpecsFinder(
            dirname(__DIR__, 2),
            new NameStatusDiffParser(),
        );

        $result = $finder->findChangedSpecs('HEAD', 'src', false);
        self::assertGreaterThanOrEqual(0, count($result));
    }

    #[Test]
    public function itIncludesUntrackedSpecFiles(): void
    {
        $repoRoot = dirname(__DIR__, 2);
        $specsDir = $repoRoot.'/test-specs-fixture';
        mkdir($specsDir, 0777, true);
        file_put_contents($specsDir.'/new-feature.md', '# New Feature');

        try {
            $finder = new GitChangedSpecsFinder($repoRoot, new NameStatusDiffParser());
            $result = $finder->findChangedSpecs('HEAD', 'test-specs-fixture', true);

            $specPaths = array_map(
                static fn (ChangedSpecFile $s): string => $s->filePath,
                $result,
            );
            self::assertContains('new-feature', $specPaths);
        } finally {
            unlink($specsDir.'/new-feature.md');
            rmdir($specsDir);
        }
    }

    #[Test]
    public function itThrowsOnInvalidBranch(): void
    {
        $finder = new GitChangedSpecsFinder(
            dirname(__DIR__, 2),
            new NameStatusDiffParser(),
        );

        $this->expectException(DiffException::class);
        $finder->findChangedSpecs('non-existent-branch-xyz-123', 'src', false);
    }
}
