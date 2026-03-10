<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Model;

final readonly class ChangedSpecFile
{
    public function __construct(
        public FileChangeType $changeType,
        public string $filePath,
    ) {
    }

    public function getFormattedOutput(): string
    {
        return sprintf('[%s] %s', $this->changeType->value, $this->filePath);
    }
}
