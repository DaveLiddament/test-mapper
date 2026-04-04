<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Diff\Git;

use DaveLiddament\TestMapper\Diff\DiffProvider;
use DaveLiddament\TestMapper\Exception\DiffException;
use DaveLiddament\TestMapper\Model\ChangedFile;
use DaveLiddament\TestMapper\Model\ChangedLineRange;
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
    public function getChangedFiles(string $compareTo, bool $includeUntracked): array
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

        $files = $this->parser->parse($process->getOutput());

        if ($includeUntracked) {
            $files = [...$files, ...$this->getUntrackedFiles()];
        }

        return $files;
    }

    /**
     * @return list<ChangedFile>
     */
    private function getUntrackedFiles(): array
    {
        $process = new Process(
            ['git', 'ls-files', '--others', '--exclude-standard'],
        );
        $process->setWorkingDirectory($this->workingDirectory);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) { // @codeCoverageIgnore
            throw new DiffException(sprintf('Failed to list untracked files: %s', $e->getMessage()), previous: $e); // @codeCoverageIgnore
        }

        $files = [];

        foreach (explode("\n", $process->getOutput()) as $line) {
            $line = trim($line);
            if ('' !== $line) {
                $files[] = new ChangedFile($line, [new ChangedLineRange(1, \PHP_INT_MAX)]);
            }
        }

        return $files;
    }
}
