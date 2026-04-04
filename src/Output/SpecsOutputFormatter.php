<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Output;

use DaveLiddament\TestMapper\Model\TestClassificationResult;
use Symfony\Component\Console\Output\OutputInterface;

final class SpecsOutputFormatter implements OutputFormatter
{
    public function format(array $changedTests, ?TestClassificationResult $classificationResult, OutputInterface $output): void
    {
        if (null === $classificationResult) {
            return;
        }

        $specs = [];
        foreach ($classificationResult->ok as $test) {
            foreach ($test->matchingSpecs as $spec) {
                $specs[$spec] = true;
            }
        }

        $specList = array_keys($specs);
        sort($specList);

        foreach ($specList as $spec) {
            $output->writeln($spec);
        }
    }
}
