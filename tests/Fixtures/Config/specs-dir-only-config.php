<?php

declare(strict_types=1);

use DaveLiddament\TestMapper\Config\TestMapperConfig;

return TestMapperConfig::create()
    ->specsDir('wrong-dir')
    ->build();
