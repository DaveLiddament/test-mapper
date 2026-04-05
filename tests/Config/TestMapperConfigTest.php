<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Config;

use DaveLiddament\TestMapper\Config\TestMapperConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestMapperConfig::class)]
final class TestMapperConfigTest extends TestCase
{
    #[Test]
    public function itHasSensibleDefaults(): void
    {
        $config = TestMapperConfig::create()->build();

        self::assertNull($config->getSpecsDir());
        self::assertSame('main', $config->getBranch());
        self::assertFalse($config->isIncludeUntracked());
        self::assertSame([], $config->getTestDirectories());
        self::assertSame([], $config->getExcludeTestDirectories());
        self::assertFalse($config->isNoSpecs());
    }

    #[Test]
    public function itSetsAllProperties(): void
    {
        $config = TestMapperConfig::create()
            ->specsDir('specs')
            ->branch('develop')
            ->includeUntracked()
            ->testDirectories('tests')
            ->excludeTestDirectories('tests/Fixtures')
            ->noSpecs()
            ->build();

        self::assertSame('specs', $config->getSpecsDir());
        self::assertSame('develop', $config->getBranch());
        self::assertTrue($config->isIncludeUntracked());
        self::assertSame(['tests'], $config->getTestDirectories());
        self::assertSame(['tests/Fixtures'], $config->getExcludeTestDirectories());
        self::assertTrue($config->isNoSpecs());
    }

    #[Test]
    public function itAcceptsMultipleTestDirectories(): void
    {
        $config = TestMapperConfig::create()
            ->testDirectories('tests', 'integration-tests', 'functional-tests')
            ->build();

        self::assertSame(['tests', 'integration-tests', 'functional-tests'], $config->getTestDirectories());
    }

    #[Test]
    public function itAcceptsMultipleExcludeTestDirectories(): void
    {
        $config = TestMapperConfig::create()
            ->excludeTestDirectories('tests/Fixtures', 'tests/Stubs')
            ->build();

        self::assertSame(['tests/Fixtures', 'tests/Stubs'], $config->getExcludeTestDirectories());
    }
}
