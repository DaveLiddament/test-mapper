<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Output;

use DaveLiddament\TestMapper\Model\TestClassificationResult;
use Symfony\Component\Console\Output\OutputInterface;

final class GitHubOutputFormatter implements OutputFormatter
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
        foreach ($changedTests as $test) {
            $output->writeln(sprintf('::notice::Changed test: %s', $test->getFullyQualifiedName()));
        }
    }

    private function formatClassified(TestClassificationResult $result, OutputInterface $output): void
    {
        foreach ($result->noTickets as $test) {
            $output->writeln(sprintf('::error::No Tickets: %s', $test->testName));
        }

        foreach ($result->unexpectedChange as $test) {
            $tickets = implode(', ', $test->ticketIds);
            $output->writeln(sprintf('::error::Unexpected Change: %s (tickets: %s)', $test->testName, $tickets));
        }

        foreach ($result->noTest as $spec) {
            $output->writeln(sprintf('::warning::No Test: %s', $spec));
        }
    }
}
