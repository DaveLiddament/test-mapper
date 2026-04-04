<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper;

use DaveLiddament\TestMapper\Model\ChangedTestMethod;

interface ChangedTestFinder
{
    /**
     * @return list<ChangedTestMethod>
     */
    public function findChangedTests(string $compareTo, bool $includeUntracked): array;
}
