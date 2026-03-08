<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\TestAnalyzer;

use DaveLiddament\TestMapper\Model\TestMethod;

interface TestMethodFinder
{
    /**
     * @return list<TestMethod>
     */
    public function findTestMethods(string $filePath): array;
}
