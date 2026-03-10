<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Specs;

use DaveLiddament\TestMapper\Model\ChangedSpecFile;

interface ChangedSpecsFinder
{
    /**
     * @return list<ChangedSpecFile>
     */
    public function findChangedSpecs(string $compareTo, string $specsDirectory): array;
}
