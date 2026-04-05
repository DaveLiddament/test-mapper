<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Config;

final class TestMapperConfig
{
    private ?string $specsDir = null;

    private string $branch = 'main';

    private bool $includeUntracked = false;

    /** @var list<string> */
    private array $testDirectories = [];

    /** @var list<string> */
    private array $excludeTestDirectories = [];

    private bool $noSpecs = false;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function specsDir(string $specsDir): self
    {
        $this->specsDir = $specsDir;

        return $this;
    }

    public function branch(string $branch): self
    {
        $this->branch = $branch;

        return $this;
    }

    public function includeUntracked(): self
    {
        $this->includeUntracked = true;

        return $this;
    }

    public function testDirectories(string ...$directories): self
    {
        $this->testDirectories = array_values($directories);

        return $this;
    }

    public function excludeTestDirectories(string ...$directories): self
    {
        $this->excludeTestDirectories = array_values($directories);

        return $this;
    }

    public function noSpecs(): self
    {
        $this->noSpecs = true;

        return $this;
    }

    public function build(): self
    {
        return $this;
    }

    public function getSpecsDir(): ?string
    {
        return $this->specsDir;
    }

    public function getBranch(): string
    {
        return $this->branch;
    }

    public function isIncludeUntracked(): bool
    {
        return $this->includeUntracked;
    }

    /**
     * @return list<string>
     */
    public function getTestDirectories(): array
    {
        return $this->testDirectories;
    }

    /**
     * @return list<string>
     */
    public function getExcludeTestDirectories(): array
    {
        return $this->excludeTestDirectories;
    }

    public function isNoSpecs(): bool
    {
        return $this->noSpecs;
    }
}
