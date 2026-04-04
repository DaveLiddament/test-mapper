<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Output;

use DaveLiddament\TestMapper\Model\TestClassificationResult;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

final class TableOutputFormatter implements OutputFormatter
{
    public function format(array $changedTests, ?TestClassificationResult $classificationResult, OutputInterface $output): void
    {
        if (null === $classificationResult) {
            $this->formatLegacy($changedTests, $output);

            return;
        }

        $this->formatClassified($classificationResult, $output);
    }

    /**
     * @param list<\DaveLiddament\TestMapper\Model\ChangedTestMethod> $changedTests
     */
    private function formatLegacy(array $changedTests, OutputInterface $output): void
    {
        if ([] === $changedTests) {
            return;
        }

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

    private function formatClassified(TestClassificationResult $result, OutputInterface $output): void
    {
        if ([] === $result->noTest && [] === $result->unexpectedChange && [] === $result->noTickets && [] === $result->ok) {
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Test', 'Tickets', 'Specs', 'Status']);

        foreach ($result->noTest as $specPath) {
            $table->addRow([
                '',
                '',
                '<comment>'.$specPath.'</comment>',
                '<comment>No Test</comment>',
            ]);
        }

        foreach ($result->unexpectedChange as $classifiedTest) {
            $table->addRow([
                '<error>'.$classifiedTest->testName.'</error>',
                '<error>'.implode("\n", $classifiedTest->ticketIds).'</error>',
                '',
                '<error>Unexpected Change</error>',
            ]);
        }

        foreach ($result->noTickets as $classifiedTest) {
            $table->addRow([
                $classifiedTest->testName,
                '',
                '',
                'No Tickets',
            ]);
        }

        foreach ($result->ok as $classifiedTest) {
            $table->addRow([
                $classifiedTest->testName,
                implode("\n", $classifiedTest->ticketIds),
                implode("\n", $classifiedTest->matchingSpecs),
                'OK',
            ]);
        }

        $table->render();
    }
}
