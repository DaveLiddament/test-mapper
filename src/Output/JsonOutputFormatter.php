<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Output;

use DaveLiddament\TestMapper\Model\TestClassificationResult;
use Symfony\Component\Console\Output\OutputInterface;

final class JsonOutputFormatter implements OutputFormatter
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
        $data = [
            'tests' => array_map(
                static fn ($test) => [
                    'name' => $test->getFullyQualifiedName(),
                    'ticketIds' => $test->ticketIds,
                ],
                $changedTests,
            ),
        ];

        $output->writeln(json_encode($data, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR));
    }

    private function formatClassified(TestClassificationResult $result, OutputInterface $output): void
    {
        $data = [
            'noTest' => $result->noTest,
            'unexpectedChange' => array_map(
                static fn ($test) => [
                    'test' => $test->testName,
                    'tickets' => $test->ticketIds,
                    'matchingSpecs' => $test->matchingSpecs,
                ],
                $result->unexpectedChange,
            ),
            'noTickets' => array_map(
                static fn ($test) => [
                    'test' => $test->testName,
                    'tickets' => $test->ticketIds,
                    'matchingSpecs' => $test->matchingSpecs,
                ],
                $result->noTickets,
            ),
            'ok' => array_map(
                static fn ($test) => [
                    'test' => $test->testName,
                    'tickets' => $test->ticketIds,
                    'matchingSpecs' => $test->matchingSpecs,
                ],
                $result->ok,
            ),
        ];

        $output->writeln(json_encode($data, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR));
    }
}
