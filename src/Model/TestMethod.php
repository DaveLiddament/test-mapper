<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Model;

final readonly class TestMethod
{
    /**
     * @param list<LineRange> $dependentRanges
     */
    public function __construct(
        public string $fullyQualifiedClassName,
        public string $methodName,
        public int $startLine,
        public int $endLine,
        public string $filePath,
        public array $dependentRanges = [],
    ) {
    }
}
