<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Diff;

use DaveLiddament\TestMapper\Model\ChangedFile;

interface DiffProvider
{
    /**
     * @return list<ChangedFile>
     */
    public function getChangedFiles(string $compareTo, bool $includeUntracked): array;
}
