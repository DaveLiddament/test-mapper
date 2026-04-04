<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Model;

final readonly class ClassifiedTest
{
    /**
     * @param list<string> $ticketIds
     * @param list<string> $matchingSpecs
     */
    public function __construct(
        public string $testName,
        public TestStatus $status,
        public array $ticketIds,
        public array $matchingSpecs,
    ) {
    }
}
