<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Config;

use DaveLiddament\TestMapper\Model\HasRelativeFilePath;

final readonly class TestDirectoryFilter
{
    /** @var list<string> */
    private array $testDirectories;

    /** @var list<string> */
    private array $excludeTestDirectories;

    private const array DEFAULT_TEST_DIRECTORIES = ['tests'];

    /**
     * @param list<string> $testDirectories
     * @param list<string> $excludeTestDirectories
     */
    public function __construct(array $testDirectories, array $excludeTestDirectories)
    {
        $this->testDirectories = [] === $testDirectories ? self::DEFAULT_TEST_DIRECTORIES : $testDirectories;
        $this->excludeTestDirectories = $excludeTestDirectories;
    }

    public static function fromConfig(TestMapperConfig $config): self
    {
        return new self($config->getTestDirectories(), $config->getExcludeTestDirectories());
    }

    /**
     * @return list<string>
     */
    public function getTestDirectories(): array
    {
        return $this->testDirectories;
    }

    public function isIncluded(HasRelativeFilePath $item): bool
    {
        $path = $item->getRelativeFilePath();

        foreach ($this->excludeTestDirectories as $excludeDir) {
            if (str_starts_with($path, $excludeDir.'/') || $path === $excludeDir) {
                return false;
            }
        }

        foreach ($this->testDirectories as $testDir) {
            if (str_starts_with($path, $testDir.'/') || $path === $testDir) {
                return true;
            }
        }

        return false;
    }

    /**
     * @template T of HasRelativeFilePath
     *
     * @param list<T> $items
     *
     * @return list<T>
     */
    public function filter(array $items): array
    {
        return array_values(array_filter($items, $this->isIncluded(...)));
    }
}
