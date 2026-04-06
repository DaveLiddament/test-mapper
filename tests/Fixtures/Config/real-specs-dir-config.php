<?php

declare(strict_types=1);

use DaveLiddament\TestMapper\Config\TestMapperConfig;

return TestMapperConfig::create()
    ->specsDir(__DIR__.'/../Output/Specs')
    ->build();
