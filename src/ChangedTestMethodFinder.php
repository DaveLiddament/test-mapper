<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper;

use DaveLiddament\TestMapper\Diff\DiffProvider;
use DaveLiddament\TestMapper\Model\ChangedTestMethod;
use DaveLiddament\TestMapper\TestAnalyzer\TestMethodFinder;

final readonly class ChangedTestMethodFinder implements ChangedTestFinder
{
    public function __construct(
        private DiffProvider $diffProvider,
        private TestMethodFinder $testMethodFinder,
    ) {
    }

    /**
     * @return list<ChangedTestMethod>
     */
    public function findChangedTests(string $compareTo, bool $includeUntracked): array
    {
        $changedFiles = $this->diffProvider->getChangedFiles($compareTo, $includeUntracked);
        $changedTestMethods = [];

        foreach ($changedFiles as $changedFile) {
            if (!str_ends_with($changedFile->filePath, '.php')) {
                continue;
            }

            $testMethods = $this->testMethodFinder->findTestMethods($changedFile->filePath);

            foreach ($testMethods as $testMethod) {
                if ($changedFile->overlapsRange($testMethod->startLine, $testMethod->endLine)) {
                    $changedTestMethods[] = new ChangedTestMethod(
                        $testMethod->fullyQualifiedClassName,
                        $testMethod->methodName,
                        $testMethod->ticketIds,
                    );
                    continue;
                }

                foreach ($testMethod->dependentRanges as $dependentRange) {
                    if ($changedFile->overlapsRange($dependentRange->startLine, $dependentRange->endLine)) {
                        $changedTestMethods[] = new ChangedTestMethod(
                            $testMethod->fullyQualifiedClassName,
                            $testMethod->methodName,
                            $testMethod->ticketIds,
                        );
                        /** @infection-ignore-all Equivalent mutant: `continue` on inner loop produces same result, just doesn't short-circuit */
                        break;
                    }
                }
            }
        }

        return $changedTestMethods;
    }
}
