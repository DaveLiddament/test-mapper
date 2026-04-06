<?php

declare(strict_types=1);

use DaveLiddament\TestMapper\Config\TestMapperConfig;

return TestMapperConfig::create()
    ->branch('develop')
    ->includeUntracked()
    ->build();
