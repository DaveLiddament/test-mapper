<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\TestAnalyzer\PhpUnit;

use DaveLiddament\TestMapper\Model\LineRange;

final readonly class ExpectedTestMethod
{
    /**
     * @param list<LineRange>|null $dependentRanges
     * @param list<string>|null $ticketIds
     */
    public function __construct(
        public ?string $fullyQualifiedClassName = null,
        public ?string $methodName = null,
        public ?int $startLine = null,
        public ?int $endLine = null,
        public ?string $filePath = null,
        public ?array $dependentRanges = null,
        public ?array $ticketIds = null,
    ) {
    }
}
