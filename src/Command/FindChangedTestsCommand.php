<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Command;

use DaveLiddament\TestMapper\ChangedTestFinder;
use DaveLiddament\TestMapper\Specs\ChangedSpecsFinder;
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
    public function __construct(
        private readonly ChangedTestFinder $changedTestFinder,
        private readonly ?ChangedSpecsFinder $changedSpecsFinder = null,
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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $branch */
        $branch = $input->getOption('branch');

        $changedTests = $this->changedTestFinder->findChangedTests($branch);

        foreach ($changedTests as $changedTest) {
            $output->writeln($changedTest->getFullyQualifiedName());
        }

        /** @var string|null $specsDir */
        $specsDir = $input->getOption('specs-dir');

        if (null !== $specsDir && null !== $this->changedSpecsFinder) {
            $changedSpecs = $this->changedSpecsFinder->findChangedSpecs($branch, $specsDir);

            foreach ($changedSpecs as $changedSpec) {
                $output->writeln($changedSpec->getFormattedOutput());
            }
        }

        return Command::SUCCESS;
    }
}
