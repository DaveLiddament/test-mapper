<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Model;

interface HasRelativeFilePath
{
    public function getRelativeFilePath(): string;
}
