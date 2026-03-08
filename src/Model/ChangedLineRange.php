<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Model;

final readonly class ChangedLineRange
{
    public function __construct(
        public int $startLine,
        public int $lineCount,
    ) {
    }

    public function overlapsRange(int $start, int $end): bool
    {
        if (0 === $this->lineCount) {
            return false;
        }

        $rangeEnd = $this->startLine + $this->lineCount - 1;

        return $this->startLine <= $end && $rangeEnd >= $start;
    }
}
