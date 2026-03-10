<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Specs;

use DaveLiddament\TestMapper\Exception\DiffException;
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

        $result = $finder->findChangedSpecs('HEAD', 'src');
        self::assertGreaterThanOrEqual(0, count($result));
    }

    #[Test]
    public function itThrowsOnInvalidBranch(): void
    {
        $finder = new GitChangedSpecsFinder(
            dirname(__DIR__, 2),
            new NameStatusDiffParser(),
        );

        $this->expectException(DiffException::class);
        $finder->findChangedSpecs('non-existent-branch-xyz-123', 'src');
    }
}
