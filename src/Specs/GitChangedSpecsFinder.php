<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Specs;

use DaveLiddament\TestMapper\Exception\DiffException;
use DaveLiddament\TestMapper\Model\ChangedSpecFile;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final readonly class GitChangedSpecsFinder implements ChangedSpecsFinder
{
    public function __construct(
        private string $workingDirectory,
        private NameStatusDiffParser $parser,
    ) {
    }

    /**
     * @return list<ChangedSpecFile>
     */
    public function findChangedSpecs(string $compareTo, string $specsDirectory): array
    {
        $process = new Process(
            ['git', 'diff', '--name-status', $compareTo, '--', $specsDirectory],
        );
        $process->setWorkingDirectory($this->workingDirectory);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            throw new DiffException(sprintf('Failed to run git diff: %s', $e->getMessage()), previous: $e);
        }

        return $this->parser->parse($process->getOutput(), $specsDirectory);
    }
}
