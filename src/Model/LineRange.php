<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Model;

final readonly class LineRange
{
    public function __construct(
        public int $startLine,
        public int $endLine,
    ) {
    }
}
