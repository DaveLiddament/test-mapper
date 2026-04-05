<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Model;

final readonly class ChangedTestMethod implements HasRelativeFilePath
{
    /**
     * @param list<string> $ticketIds
     */
    public function __construct(
        public string $fullyQualifiedClassName,
        public string $methodName,
        public array $ticketIds = [],
        public string $filePath = '',
    ) {
    }

    public function getFullyQualifiedName(): string
    {
        return $this->fullyQualifiedClassName.'::'.$this->methodName;
    }

    public function getRelativeFilePath(): string
    {
        return $this->filePath;
    }
}
