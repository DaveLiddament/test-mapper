<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Model;

final readonly class ChangedFile
{
    /**
     * @param list<ChangedLineRange> $changedLineRanges
     */
    public function __construct(
        public string $filePath,
        public array $changedLineRanges,
    ) {
    }

    public function overlapsRange(int $startLine, int $endLine): bool
    {
        foreach ($this->changedLineRanges as $changedLineRange) {
            if ($changedLineRange->overlapsRange($startLine, $endLine)) {
                return true;
            }
        }

        return false;
    }
}
