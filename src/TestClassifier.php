<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper;

use DaveLiddament\TestMapper\Model\ChangedSpecFile;
use DaveLiddament\TestMapper\Model\ChangedTestMethod;
use DaveLiddament\TestMapper\Model\ClassifiedTest;
use DaveLiddament\TestMapper\Model\TestClassificationResult;
use DaveLiddament\TestMapper\Model\TestStatus;

final class TestClassifier
{
    /**
     * @param list<ChangedTestMethod> $changedTests
     * @param list<ChangedSpecFile> $changedSpecs
     */
    public function classify(array $changedTests, array $changedSpecs): TestClassificationResult
    {
        $specFilePaths = array_map(
            static fn (ChangedSpecFile $spec): string => $spec->filePath,
            $changedSpecs,
        );
        $specFilePathSet = array_flip($specFilePaths);

        $allTicketIds = [];
        foreach ($changedTests as $test) {
            foreach ($test->ticketIds as $ticketId) {
                /** @infection-ignore-all Equivalent mutant: value is irrelevant, only key existence matters via isset() */
                $allTicketIds[$ticketId] = true;
            }
        }

        $noTest = [];
        foreach ($specFilePaths as $specFilePath) {
            if (!isset($allTicketIds[$specFilePath])) {
                $noTest[] = $specFilePath;
            }
        }
        sort($noTest);

        $unexpectedChange = [];
        $noTickets = [];
        $ok = [];

        foreach ($changedTests as $test) {
            $testName = $test->getFullyQualifiedName();

            if ([] === $test->ticketIds) {
                $noTickets[] = new ClassifiedTest($testName, TestStatus::NoTickets, [], []);
                continue;
            }

            $matchingSpecs = [];
            foreach ($test->ticketIds as $ticketId) {
                if (isset($specFilePathSet[$ticketId])) {
                    $matchingSpecs[] = $ticketId;
                }
            }

            if ([] === $matchingSpecs) {
                $unexpectedChange[] = new ClassifiedTest($testName, TestStatus::UnexpectedChange, $test->ticketIds, []);
                continue;
            }

            sort($matchingSpecs);
            $ok[] = new ClassifiedTest($testName, TestStatus::Ok, $test->ticketIds, $matchingSpecs);
        }

        usort($unexpectedChange, static fn (ClassifiedTest $a, ClassifiedTest $b): int => $a->testName <=> $b->testName);
        usort($noTickets, static fn (ClassifiedTest $a, ClassifiedTest $b): int => $a->testName <=> $b->testName);
        usort($ok, static fn (ClassifiedTest $a, ClassifiedTest $b): int => $a->testName <=> $b->testName);

        return new TestClassificationResult($noTest, $unexpectedChange, $noTickets, $ok);
    }
}
