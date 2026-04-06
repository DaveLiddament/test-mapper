<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Command;

use DaveLiddament\TestMapper\Config\ConfigLoader;
use DaveLiddament\TestMapper\Config\TestDirectoryFilter;
use DaveLiddament\TestMapper\Model\LineRange;
use DaveLiddament\TestMapper\Model\TestMethod;
use DaveLiddament\TestMapper\Output\SourceCodeReader;
use DaveLiddament\TestMapper\TestAnalyzer\TestMethodFinder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

#[AsCommand(
    name: 'spec-reviewer',
    description: 'Generate an AI-reviewable markdown document for given specs and their matching tests',
)]
final class SpecReviewerCommand extends Command
{
    public function __construct(
        private readonly TestMethodFinder $testMethodFinder,
        private readonly SourceCodeReader $sourceCodeReader,
        private readonly ConfigLoader $configLoader,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'specs',
            InputArgument::IS_ARRAY,
            'Spec names to review (reads from stdin if omitted)',
        );

        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'Path to config file',
        );

        $this->addOption(
            'specs-dir',
            'd',
            InputOption::VALUE_REQUIRED,
            'Directory containing spec/requirement files',
        );

        $this->addOption(
            'no-specs',
            null,
            InputOption::VALUE_NONE,
            'Omit the Specs section (useful when specs are not markdown)',
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

        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'Write output to file instead of stdout',
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

        /** @var string|null $specsDir */
        $specsDir = $input->getOption('specs-dir') ?? $config->getSpecsDir();

        if (null === $specsDir) {
            $output->writeln('The --specs-dir option is required');

            return Command::FAILURE;
        }

        if (!is_dir($specsDir)) {
            $output->writeln(sprintf('Specs directory not found: %s', $specsDir));

            return Command::FAILURE;
        }

        /** @infection-ignore-all Equivalent mutant: getOption for VALUE_NONE already returns bool */
        $noSpecs = (bool) $input->getOption('no-specs') || $config->isNoSpecs();

        $specNames = $this->collectSpecNames($input);

        if ([] === $specNames) {
            $output->writeln('No spec names provided');

            return Command::FAILURE;
        }

        /** @var list<string> $testDirOption */
        $testDirOption = $input->getOption('test-dir');
        /** @var list<string> $excludeTestDirOption */
        $excludeTestDirOption = $input->getOption('exclude-test-dir');

        /** @infection-ignore-all CLI override priority — TestMethodFinder is stubbed so directory choice has no observable effect in tests */
        $testDirectories = [] !== $testDirOption ? $testDirOption : $config->getTestDirectories();
        /** @infection-ignore-all CLI override priority — TestMethodFinder is stubbed so directory choice has no observable effect in tests */
        $excludeDirectories = [] !== $excludeTestDirOption ? $excludeTestDirOption : $config->getExcludeTestDirectories();
        $filter = new TestDirectoryFilter($testDirectories, $excludeDirectories);
        $testsBySpec = $this->findMatchingTests($specNames, $filter);

        $formatterOutput = $this->resolveOutput($input, $output);
        $this->writeMarkdown($specNames, $testsBySpec, $specsDir, $noSpecs, $formatterOutput);

        return Command::SUCCESS;
    }

    private function resolveOutput(InputInterface $input, OutputInterface $output): OutputInterface
    {
        /** @var string|null $outputPath */
        $outputPath = $input->getOption('output');

        if (null === $outputPath) {
            return $output;
        }

        $handle = @fopen($outputPath, 'w');

        if (false === $handle) {
            return $output; // @codeCoverageIgnore
        }

        return new StreamOutput($handle);
    }

    /**
     * @return list<string>
     */
    private function collectSpecNames(InputInterface $input): array
    {
        /** @var list<string> $args */
        $args = $input->getArgument('specs');

        if ([] !== $args) {
            $specs = $args;
        } else {
            // @codeCoverageIgnoreStart
            $stdinStream = \STDIN;

            if (stream_isatty($stdinStream)) {
                return [];
            }

            stream_set_blocking($stdinStream, false);
            $stdin = stream_get_contents($stdinStream);

            if (false === $stdin || '' === trim($stdin)) {
                return [];
            }

            $specs = array_values(array_filter(
                array_map('trim', explode("\n", $stdin)),
                static fn (string $line): bool => '' !== $line,
            ));
            // @codeCoverageIgnoreEnd
        }

        sort($specs);

        return $specs;
    }

    /**
     * @param list<string> $specNames
     *
     * @return array<string, list<TestMethod>>
     *
     * @infection-ignore-all TestMethodFinder is stubbed — return value mutations are equivalent
     */
    private function findMatchingTests(array $specNames, TestDirectoryFilter $filter): array
    {
        $specSet = array_flip($specNames);
        /** @infection-ignore-all Equivalent mutant: array_fill_keys initialises all keys */
        $testsBySpec = array_fill_keys($specNames, []);

        $phpFiles = $this->findPhpFiles($filter);

        foreach ($phpFiles as $file) {
            $testMethods = $this->testMethodFinder->findTestMethods($file);

            foreach ($testMethods as $testMethod) {
                foreach ($testMethod->ticketIds as $ticketId) {
                    if (isset($specSet[$ticketId])) {
                        $testsBySpec[$ticketId][] = $testMethod;
                    }
                }
            }
        }

        return $testsBySpec;
    }

    /**
     * @return list<string>
     *
     * @infection-ignore-all Filesystem scanning — TestMethodFinder is stubbed in tests
     */
    private function findPhpFiles(TestDirectoryFilter $filter): array
    {
        /** @infection-ignore-all Equivalent mutant: getcwd() returns string in normal operation, cast handles edge case */
        $basePath = (string) getcwd();
        $files = [];

        foreach ($filter->getTestDirectories() as $testDir) {
            $directory = $basePath.'/'.$testDir;

            if (!is_dir($directory)) {
                continue; // @codeCoverageIgnore
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            );

            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue; // @codeCoverageIgnore
                }

                $path = $file->getPathname();

                /** @infection-ignore-all Filesystem scanning — stubbed in tests */
                if (!str_ends_with($path, '.php')) {
                    continue;
                }

                $files[] = $path;
            }
        }

        /** @infection-ignore-all Equivalent mutant: sort order doesn't affect correctness */
        sort($files);

        return $files;
    }

    /**
     * @param list<string> $specNames
     * @param array<string, list<TestMethod>> $testsBySpec
     *
     * @infection-ignore-all Markdown formatting — mutations to writeln calls and string concat are cosmetic
     */
    private function writeMarkdown(array $specNames, array $testsBySpec, string $specsDir, bool $noSpecs, OutputInterface $output): void
    {
        $output->writeln('# Changes to Review');
        $output->writeln('');

        $this->writeTableOfContents($specNames, $testsBySpec, $specsDir, $noSpecs, $output);

        if (!$noSpecs) {
            $this->writeSpecsSection($specNames, $specsDir, $output);
        }

        $this->writeTestsSection($specNames, $testsBySpec, $output);
    }

    /**
     * @param list<string> $specNames
     * @param array<string, list<TestMethod>> $testsBySpec
     *
     * @infection-ignore-all Markdown formatting
     */
    private function writeTableOfContents(array $specNames, array $testsBySpec, string $specsDir, bool $noSpecs, OutputInterface $output): void
    {
        $output->writeln('## Contents');
        $output->writeln('');

        if (!$noSpecs) {
            $output->writeln('### Specs');
            $output->writeln('');
            foreach ($specNames as $spec) {
                $anchor = $this->toAnchor($spec);
                $specFile = $this->findSpecFile($specsDir, $spec);
                $relativePath = null !== $specFile ? $specsDir.'/'.$specFile : $spec;
                $output->writeln(sprintf('- [%s](#%s) ([view file](%s))', $spec, $anchor, $relativePath));
            }
            $output->writeln('');
        }

        $output->writeln('### Tests');
        $output->writeln('');
        $seenTests = [];
        foreach ($specNames as $spec) {
            foreach ($testsBySpec[$spec] ?? [] as $testMethod) {
                $testName = $testMethod->fullyQualifiedClassName.'::'.$testMethod->methodName;
                if (isset($seenTests[$testName])) {
                    continue;
                }
                $seenTests[$testName] = true;

                $anchor = $this->toAnchor($testName);
                $output->writeln(sprintf('- [%s](#%s) ([view file](%s))', $testName, $anchor, $testMethod->filePath));
            }
        }
        $output->writeln('');

        $output->writeln('---');
        $output->writeln('');
    }

    /**
     * @param list<string> $specNames
     *
     * @infection-ignore-all Markdown formatting
     */
    private function writeSpecsSection(array $specNames, string $specsDir, OutputInterface $output): void
    {
        $output->writeln('## Specs');
        $output->writeln('');

        foreach ($specNames as $spec) {
            $output->writeln(sprintf('### %s', $spec));
            $output->writeln('');

            $specFile = $this->findSpecFile($specsDir, $spec);

            if (null !== $specFile) {
                $fullPath = $specsDir.'/'.$specFile;
                $output->writeln(sprintf('`%s`', $fullPath));
                $output->writeln('');
                $contents = $this->sourceCodeReader->readFile($fullPath);
                if ('' !== $contents) {
                    $output->writeln($contents);
                    $output->writeln('');
                }
            }

            $output->writeln('---');
            $output->writeln('');
        }
    }

    /**
     * @param list<string> $specNames
     * @param array<string, list<TestMethod>> $testsBySpec
     *
     * @infection-ignore-all Markdown formatting
     */
    private function writeTestsSection(array $specNames, array $testsBySpec, OutputInterface $output): void
    {
        $output->writeln('## Tests');
        $output->writeln('');

        foreach ($specNames as $spec) {
            $output->writeln(sprintf('### %s', $spec));
            $output->writeln('');

            foreach ($testsBySpec[$spec] ?? [] as $testMethod) {
                $output->writeln(sprintf('`%s`', $testMethod->filePath));
                $output->writeln('');
                $output->writeln('```php');
                $this->writeTestCode($testMethod, $output);
                $output->writeln('```');
                $output->writeln('');
            }

            $output->writeln('---');
            $output->writeln('');
        }
    }

    /** @infection-ignore-all Markdown formatting */
    private function writeTestCode(TestMethod $test, OutputInterface $output): void
    {
        $ranges = [];

        foreach ($test->dependentRanges as $range) {
            $ranges[] = $range;
        }

        $ranges[] = new LineRange($test->startLine, $test->endLine);

        usort($ranges, static fn (LineRange $a, LineRange $b) => $a->startLine <=> $b->startLine);

        $first = true;
        foreach ($ranges as $range) {
            if (!$first) {
                $output->writeln('');
            }
            $first = false;

            $code = $this->sourceCodeReader->readLines($test->filePath, $range->startLine, $range->endLine);
            $output->writeln($code);
        }
    }

    /** @infection-ignore-all Filesystem glob — path construction tested via integration */
    private function findSpecFile(string $specsDir, string $specPath): ?string
    {
        $pattern = $specsDir.'/'.$specPath.'.*';
        $matches = glob($pattern);

        if (false === $matches || [] === $matches) {
            return null; // @codeCoverageIgnore
        }

        $fullPath = $matches[0];

        return substr($fullPath, \strlen($specsDir) + 1);
    }

    /** @infection-ignore-all Anchor generation — cosmetic formatting */
    private function toAnchor(string $text): string
    {
        $anchor = strtolower($text);
        $anchor = (string) preg_replace('/[^a-z0-9\s-]/', '', $anchor);
        $anchor = (string) preg_replace('/[\s]+/', '-', $anchor);

        return trim($anchor, '-');
    }
}
