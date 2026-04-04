<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Command;

use DaveLiddament\TestMapper\ChangedTestFinder;
use DaveLiddament\TestMapper\Output\OutputFormatter;
use DaveLiddament\TestMapper\Output\TableOutputFormatter;
use DaveLiddament\TestMapper\Specs\ChangedSpecsFinder;
use DaveLiddament\TestMapper\TestClassifier;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'find-changed-tests',
    description: 'Find test methods that have changed compared to a target branch',
)]
final class FindChangedTestsCommand extends Command
{
    /**
     * @param array<string, OutputFormatter> $formatters
     */
    public function __construct(
        private readonly ChangedTestFinder $changedTestFinder,
        private readonly TestClassifier $testClassifier,
        private readonly ?ChangedSpecsFinder $changedSpecsFinder = null,
        private readonly array $formatters = [],
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'branch',
            'b',
            InputOption::VALUE_REQUIRED,
            'The branch to compare against',
            'main',
        );

        $this->addOption(
            'specs-dir',
            'd',
            InputOption::VALUE_REQUIRED,
            'Directory containing spec/requirement files',
        );

        $this->addOption(
            'format',
            'f',
            InputOption::VALUE_REQUIRED,
            'Output format (table, json)',
            'table',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $branch */
        $branch = $input->getOption('branch');

        /** @var string $format */
        $format = $input->getOption('format');

        $changedTests = $this->changedTestFinder->findChangedTests($branch);

        /** @var string|null $specsDir */
        $specsDir = $input->getOption('specs-dir');

        $classificationResult = null;

        if (null !== $specsDir && null !== $this->changedSpecsFinder) {
            $changedSpecs = $this->changedSpecsFinder->findChangedSpecs($branch, $specsDir);
            $classificationResult = $this->testClassifier->classify($changedTests, $changedSpecs);
        }

        $formatter = $this->formatters[$format] ?? new TableOutputFormatter();
        $formatter->format($changedTests, $classificationResult, $output);

        if (null !== $classificationResult) {
            return $classificationResult->getExitCode();
        }

        return Command::SUCCESS;
    }
}
