<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Output;

use Symfony\Component\Console\Output\OutputInterface;

final class JsonOutputFormatter implements OutputFormatter
{
    public function format(array $changedTests, array $changedSpecs, OutputInterface $output): void
    {
        $data = [
            'tests' => array_map(
                static fn ($test) => [
                    'name' => $test->getFullyQualifiedName(),
                    'ticketIds' => $test->ticketIds,
                ],
                $changedTests,
            ),
            /** @infection-ignore-all Equivalent mutant: json_encode on ChangedSpecFile object produces identical output to explicit array */
            'specs' => array_map(
                static fn ($spec) => [
                    'changeType' => $spec->changeType->value,
                    'filePath' => $spec->filePath,
                ],
                $changedSpecs,
            ),
        ];

        $output->writeln(json_encode($data, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR));
    }
}
