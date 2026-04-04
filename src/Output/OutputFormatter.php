<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Output;

use DaveLiddament\TestMapper\Model\ChangedTestMethod;
use DaveLiddament\TestMapper\Model\TestClassificationResult;
use Symfony\Component\Console\Output\OutputInterface;

interface OutputFormatter
{
    /**
     * @param list<ChangedTestMethod> $changedTests
     */
    public function format(
        array $changedTests,
        ?TestClassificationResult $classificationResult,
        OutputInterface $output,
    ): void;
}
