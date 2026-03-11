<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Model;

final readonly class ChangedTestMethod
{
    /**
     * @param list<string> $ticketIds
     */
    public function __construct(
        public string $fullyQualifiedClassName,
        public string $methodName,
        public array $ticketIds = [],
    ) {
    }

    public function getFullyQualifiedName(): string
    {
        return $this->fullyQualifiedClassName.'::'.$this->methodName;
    }
}
