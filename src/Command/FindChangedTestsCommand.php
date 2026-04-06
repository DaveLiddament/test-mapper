<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Command;

use DaveLiddament\TestMapper\ChangedTestFinder;
use DaveLiddament\TestMapper\Config\ConfigLoader;
use DaveLiddament\TestMapper\Config\TestDirectoryFilter;
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
        private readonly ConfigLoader $configLoader,
        private readonly ?ChangedSpecsFinder $changedSpecsFinder = null,
        private readonly array $formatters = [],
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'Path to config file',
        );

        $this->addOption(
            'branch',
            'b',
            InputOption::VALUE_REQUIRED,
            'The branch to compare against',
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
            'Output format (table, json, specs, github)',
            'table',
        );

        $this->addOption(
            'include-untracked',
            'u',
            InputOption::VALUE_NONE,
            'Include untracked files (files not yet staged)',
        );

        $this->addOption(
            'test-dir',
            't',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Test directory to scan (overrides config; default: tests)',
        );

        $this->addOption(
            'exclude-test-dir',
            'e',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Test directory to exclude (overrides config)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string|null $configPath */
        $configPath = $input->getOption('config');

        try {
            $config = $this->configLoader->load($configPath);
        } catch (\RuntimeException $e) {
            $output->writeln($e->getMessage());

            return Command::FAILURE;
        }

        /** @var string|null $branchOption */
        $branchOption = $input->getOption('branch');
        $branch = $branchOption ?? $config->getBranch();

        /** @var string $format */
        $format = $input->getOption('format');

        /** @infection-ignore-all Equivalent mutant: getOption for VALUE_NONE already returns bool */
        $includeUntracked = (bool) $input->getOption('include-untracked') || $config->isIncludeUntracked();

        /** @var string|null $specsDir */
        $specsDir = $input->getOption('specs-dir') ?? $config->getSpecsDir();

        if (null !== $specsDir && !is_dir($specsDir)) {
            $output->writeln(sprintf('Specs directory not found: %s', $specsDir));

            return Command::FAILURE;
        }

        /** @var list<string> $testDirOption */
        $testDirOption = $input->getOption('test-dir');
        /** @var list<string> $excludeTestDirOption */
        $excludeTestDirOption = $input->getOption('exclude-test-dir');

        $testDirectories = [] !== $testDirOption ? $testDirOption : $config->getTestDirectories();
        $excludeDirectories = [] !== $excludeTestDirOption ? $excludeTestDirOption : $config->getExcludeTestDirectories();
        $filter = new TestDirectoryFilter($testDirectories, $excludeDirectories);
        $changedTests = $filter->filter($this->changedTestFinder->findChangedTests($branch, $includeUntracked));

        $classificationResult = null;

        if (null !== $specsDir && null !== $this->changedSpecsFinder) {
            $changedSpecs = $this->changedSpecsFinder->findChangedSpecs($branch, $specsDir, $includeUntracked);
            $classificationResult = $this->testClassifier->classify($changedTests, $changedSpecs);
        }

        $formatter = $this->formatters[$format] ?? new TableOutputFormatter();
        $formatter->format($changedTests, $classificationResult, $formatterOutput);

        if (null !== $classificationResult) {
            return $classificationResult->getExitCode();
        }

        return Command::SUCCESS;
    }
}
