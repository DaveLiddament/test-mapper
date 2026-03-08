<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Command;

use DaveLiddament\TestMapper\ChangedTestFinder;
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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $branch */
        $branch = $input->getOption('branch');

        $changedTests = $this->changedTestFinder->findChangedTests($branch);

        foreach ($changedTests as $changedTest) {
            $output->writeln($changedTest->getFullyQualifiedName());
        }

        return Command::SUCCESS;
    }
}
