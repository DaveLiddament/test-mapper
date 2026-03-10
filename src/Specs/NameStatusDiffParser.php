<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Specs;

use DaveLiddament\TestMapper\Model\ChangedSpecFile;
use DaveLiddament\TestMapper\Model\FileChangeType;

final class NameStatusDiffParser
{
    /**
     * @return list<ChangedSpecFile>
     */
    public function parse(string $output, string $prefixToStrip): array
    {
        $results = [];

        $prefix = rtrim($prefixToStrip, '/').'/';

        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }

            $parts = explode("\t", $line);
            if (count($parts) < 2) {
                continue;
            }

            $status = $parts[0];
            $statusLetter = $status[0];

            match ($statusLetter) {
                'A' => $results[] = new ChangedSpecFile(
                    FileChangeType::Added,
                    $this->stripPrefix($parts[1], $prefix),
                ),
                'M' => $results[] = new ChangedSpecFile(
                    FileChangeType::Modified,
                    $this->stripPrefix($parts[1], $prefix),
                ),
                'D' => $results[] = new ChangedSpecFile(
                    FileChangeType::Deleted,
                    $this->stripPrefix($parts[1], $prefix),
                ),
                'R' => $this->handleRename($parts, $prefix, $results),
                'C' => $results[] = new ChangedSpecFile(
                    FileChangeType::Added,
                    $this->stripPrefix($parts[2], $prefix),
                ),
                'T' => $results[] = new ChangedSpecFile(
                    FileChangeType::Modified,
                    $this->stripPrefix($parts[1], $prefix),
                ),
                default => null,
            };
        }

        return $results;
    }

    private function stripPrefix(string $path, string $prefix): string
    {
        if (str_starts_with($path, $prefix)) {
            return substr($path, strlen($prefix));
        }

        return $path;
    }

    /**
     * @param list<string> $parts
     * @param list<ChangedSpecFile> $results
     */
    private function handleRename(array $parts, string $prefix, array &$results): void
    {
        if (count($parts) < 3) {
            return;
        }

        $results[] = new ChangedSpecFile(
            FileChangeType::Deleted,
            $this->stripPrefix($parts[1], $prefix),
        );
        $results[] = new ChangedSpecFile(
            FileChangeType::Added,
            $this->stripPrefix($parts[2], $prefix),
        );
    }
}
