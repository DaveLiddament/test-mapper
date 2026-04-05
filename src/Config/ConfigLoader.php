<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Config;

final class ConfigLoader
{
    private const string DEFAULT_CONFIG_FILE = '.test-mapper.php';

    public function load(?string $configPath): TestMapperConfig
    {
        if (null !== $configPath) {
            if (!file_exists($configPath)) {
                throw new \RuntimeException(sprintf('Config file not found: %s', $configPath));
            }

            return $this->requireConfig($configPath);
        }

        /** @infection-ignore-all Path construction only exercised when default config exists at cwd */
        $defaultPath = getcwd().'/'.self::DEFAULT_CONFIG_FILE;

        if (!file_exists($defaultPath)) {
            return TestMapperConfig::create()->build();
        }

        return $this->requireConfig($defaultPath); // @codeCoverageIgnore
    }

    private function requireConfig(string $path): TestMapperConfig
    {
        $result = require $path;

        if (!$result instanceof TestMapperConfig) {
            throw new \RuntimeException(sprintf('Config file must return an instance of %s', TestMapperConfig::class));
        }

        return $result;
    }
}
