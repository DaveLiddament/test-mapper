<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Output;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

final class TableOutputFormatter implements OutputFormatter
{
    public function format(array $changedTests, array $changedSpecs, OutputInterface $output): void
    {
        if ([] !== $changedTests) {
            $table = new Table($output);
            $table->setHeaders(['Test', 'Tickets']);

            foreach ($changedTests as $changedTest) {
                $table->addRow([
                    $changedTest->getFullyQualifiedName(),
                    implode("\n", $changedTest->ticketIds),
                ]);
            }

            $table->render();
        }

        if ([] !== $changedSpecs) {
            $table = new Table($output);
            $table->setHeaders(['Change Type', 'File Path']);

            foreach ($changedSpecs as $changedSpec) {
                $table->addRow([
                    $changedSpec->changeType->value,
                    $changedSpec->filePath,
                ]);
            }

            $table->render();
        }
    }
}
