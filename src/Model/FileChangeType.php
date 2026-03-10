<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Model;

enum FileChangeType: string
{
    case Added = 'added';
    case Modified = 'modified';
    case Deleted = 'deleted';
}
