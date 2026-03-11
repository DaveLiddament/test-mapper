<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Output;

use DaveLiddament\TestMapper\Model\ChangedSpecFile;
use DaveLiddament\TestMapper\Model\ChangedTestMethod;
use Symfony\Component\Console\Output\OutputInterface;

interface OutputFormatter
{
    /**
     * @param list<ChangedTestMethod> $changedTests
     * @param list<ChangedSpecFile> $changedSpecs
     */
    public function format(array $changedTests, array $changedSpecs, OutputInterface $output): void;
}
