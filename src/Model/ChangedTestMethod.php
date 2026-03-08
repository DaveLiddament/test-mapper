<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Model;

final readonly class ChangedTestMethod
{
    public function __construct(
        public string $fullyQualifiedClassName,
        public string $methodName,
    ) {
    }

    public function getFullyQualifiedName(): string
    {
        return $this->fullyQualifiedClassName.'::'.$this->methodName;
    }
}
