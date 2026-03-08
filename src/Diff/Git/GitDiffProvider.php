<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Diff\Git;

use DaveLiddament\TestMapper\Diff\DiffProvider;
use DaveLiddament\TestMapper\Exception\DiffException;
use DaveLiddament\TestMapper\Model\ChangedFile;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final readonly class GitDiffProvider implements DiffProvider
{
    public function __construct(
        private string $workingDirectory,
        private GitDiffParser $parser,
    ) {
    }

    /**
     * @return list<ChangedFile>
     */
    public function getChangedFiles(string $compareTo): array
    {
        $process = new Process(
            ['git', 'diff', $compareTo, '--unified=0', '--no-color', '--no-ext-diff'],
        );
        $process->setWorkingDirectory($this->workingDirectory);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            throw new DiffException(sprintf('Failed to run git diff: %s', $e->getMessage()), previous: $e);
        }

        return $this->parser->parse($process->getOutput());
    }
}
