<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Model;

final readonly class TestClassificationResult
{
    /**
     * @param list<string> $noTest
     * @param list<ClassifiedTest> $unexpectedChange
     * @param list<ClassifiedTest> $noTickets
     * @param list<ClassifiedTest> $ok
     */
    public function __construct(
        public array $noTest,
        public array $unexpectedChange,
        public array $noTickets,
        public array $ok,
    ) {
    }

    public function getExitCode(): int
    {
        $exitCode = 0;

        if ([] !== $this->noTickets) {
            /** @infection-ignore-all Equivalent mutant: $exitCode is always 0 at this point, so |= and = are identical */
            $exitCode |= 1;
        }

        if ([] !== $this->unexpectedChange) {
            $exitCode |= 2;
        }

        if ([] !== $this->noTest) {
            $exitCode |= 4;
        }

        return $exitCode;
    }
}
