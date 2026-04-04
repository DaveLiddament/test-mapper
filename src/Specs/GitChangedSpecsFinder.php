<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Specs;

use DaveLiddament\TestMapper\Exception\DiffException;
use DaveLiddament\TestMapper\Model\ChangedSpecFile;
use DaveLiddament\TestMapper\Model\FileChangeType;
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
    public function findChangedSpecs(string $compareTo, string $specsDirectory, bool $includeUntracked): array
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

        $results = $this->parser->parse($process->getOutput(), $specsDirectory);

        if ($includeUntracked) {
            $results = [...$results, ...$this->getUntrackedSpecFiles($specsDirectory)];
        }

        return $results;
    }

    /**
     * @return list<ChangedSpecFile>
     */
    private function getUntrackedSpecFiles(string $specsDirectory): array
    {
        $process = new Process(
            ['git', 'ls-files', '--others', '--exclude-standard', '--', $specsDirectory],
        );
        $process->setWorkingDirectory($this->workingDirectory);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) { // @codeCoverageIgnore
            throw new DiffException(sprintf('Failed to list untracked spec files: %s', $e->getMessage()), previous: $e); // @codeCoverageIgnore
        }

        $prefix = rtrim($specsDirectory, '/').'/';
        $results = [];

        foreach (explode("\n", $process->getOutput()) as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }

            $path = $line;
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }

            $extension = pathinfo($path, \PATHINFO_EXTENSION);
            if ('' !== $extension) {
                $path = substr($path, 0, -strlen($extension) - 1);
            }

            $results[] = new ChangedSpecFile(FileChangeType::Added, $path);
        }

        return $results;
    }
}
