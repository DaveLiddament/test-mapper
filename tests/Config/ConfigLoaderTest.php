<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Config;

use DaveLiddament\TestMapper\Config\ConfigLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigLoader::class)]
#[Ticket('016-config-file')]
final class ConfigLoaderTest extends TestCase
{
    private ConfigLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new ConfigLoader();
    }

    #[Test]
    public function itReturnsDefaultsWhenNoConfigFileExists(): void
    {
        $config = $this->loader->load(null);

        self::assertNull($config->getSpecsDir());
        self::assertSame('main', $config->getBranch());
        self::assertFalse($config->isIncludeUntracked());
    }

    #[Test]
    public function itLoadsValidConfigFile(): void
    {
        $config = $this->loader->load($this->fixturePath('valid-config.php'));

        self::assertSame('specs', $config->getSpecsDir());
        self::assertSame('develop', $config->getBranch());
        self::assertTrue($config->isIncludeUntracked());
        self::assertSame(['tests', 'integration-tests'], $config->getTestDirectories());
        self::assertSame(['tests/Fixtures'], $config->getExcludeTestDirectories());
        self::assertTrue($config->isNoSpecs());
    }

    #[Test]
    public function itThrowsExceptionWhenSpecifiedConfigPathDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Config file not found');

        $this->loader->load('/non/existent/config.php');
    }

    #[Test]
    public function itThrowsExceptionWhenConfigFileReturnsWrongType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must return an instance of');

        $this->loader->load($this->fixturePath('invalid-config.php'));
    }

    private function fixturePath(string $filename): string
    {
        return __DIR__.'/../Fixtures/Config/'.$filename;
    }
}
