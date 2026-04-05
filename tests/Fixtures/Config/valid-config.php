<?php

declare(strict_types=1);

use DaveLiddament\TestMapper\Config\TestMapperConfig;

return TestMapperConfig::create()
    ->specsDir('specs')
    ->branch('develop')
    ->includeUntracked()
    ->testDirectories('tests', 'integration-tests')
    ->excludeTestDirectories('tests/Fixtures')
    ->noSpecs()
    ->build();
