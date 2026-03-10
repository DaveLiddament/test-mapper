<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Diff\Git;

use DaveLiddament\TestMapper\Model\ChangedFile;
use DaveLiddament\TestMapper\Model\ChangedLineRange;

final class GitDiffParser
{
    /**
     * @return list<ChangedFile>
     */
    public function parse(string $rawDiff): array
    {
        /** @infection-ignore-all Equivalent mutant: empty/whitespace input falls through to same [] result */
        if ('' === trim($rawDiff)) {
            return [];
        }

        $files = [];
        $currentFilePath = null;
        /** @var list<ChangedLineRange> $currentRanges */
        $currentRanges = [];
        /** @infection-ignore-all Equivalent mutant: initial value is irrelevant as $currentFilePath is null at this point */
        $isDeletedFile = false;

        $lines = explode("\n", $rawDiff);

        foreach ($lines as $line) {
            if (str_starts_with($line, 'diff --git ')) {
                if (null !== $currentFilePath && !$isDeletedFile) {
                    $files[] = new ChangedFile($currentFilePath, $currentRanges);
                }

                $currentFilePath = null;
                $currentRanges = [];
                $isDeletedFile = false;

                continue;
            }

            if ('+++ /dev/null' === $line) {
                $isDeletedFile = true;

                continue;
            }

            if (str_starts_with($line, '+++ b/')) {
                $currentFilePath = substr($line, 6);

                continue;
            }

            if (str_starts_with($line, '@@')) {
                $range = $this->parseHunkHeader($line);
                if (null !== $range) {
                    $currentRanges[] = $range;
                }
            }
        }

        if (null !== $currentFilePath && !$isDeletedFile) {
            $files[] = new ChangedFile($currentFilePath, $currentRanges);
        }

        return $files;
    }

    private function parseHunkHeader(string $line): ?ChangedLineRange
    {
        // Format: @@ -old[,count] +new[,count] @@
        /** @infection-ignore-all Equivalent mutant: ^ anchor is redundant as caller already checks str_starts_with($line, '@@') */
        if (1 !== preg_match('/^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@/', $line, $matches)) {
            return null;
        }

        $startLine = (int) $matches[1];
        $lineCount = isset($matches[2]) ? (int) $matches[2] : 1;

        if (0 === $lineCount) {
            return null;
        }

        return new ChangedLineRange($startLine, $lineCount);
    }
}
