<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Config;

use DaveLiddament\TestMapper\Config\TestDirectoryFilter;
use DaveLiddament\TestMapper\Config\TestMapperConfig;
use DaveLiddament\TestMapper\Model\HasRelativeFilePath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestDirectoryFilter::class)]
final class TestDirectoryFilterTest extends TestCase
{
    #[Test]
    public function itDefaultsToTestsDirectory(): void
    {
        $filter = new TestDirectoryFilter([], []);

        self::assertTrue($filter->isIncluded($this->item('tests/FooTest.php')));
        self::assertFalse($filter->isIncluded($this->item('src/Foo.php')));
        self::assertFalse($filter->isIncluded($this->item('testing/Foo.php')));
    }

    #[Test]
    public function itUsesConfiguredTestDirectories(): void
    {
        $filter = new TestDirectoryFilter(['tests', 'integration'], []);

        self::assertTrue($filter->isIncluded($this->item('tests/FooTest.php')));
        self::assertTrue($filter->isIncluded($this->item('integration/BarTest.php')));
        self::assertFalse($filter->isIncluded($this->item('src/Foo.php')));
        self::assertFalse($filter->isIncluded($this->item('integrations/Foo.php')));
    }

    #[Test]
    public function itExcludesDirectories(): void
    {
        $filter = new TestDirectoryFilter(['tests'], ['tests/Fixtures']);

        self::assertTrue($filter->isIncluded($this->item('tests/FooTest.php')));
        self::assertFalse($filter->isIncluded($this->item('tests/Fixtures/SomeFixture.php')));
        self::assertTrue($filter->isIncluded($this->item('tests/FixturesExtra/SomeTest.php')));
    }

    #[Test]
    public function itExcludesBeforeIncludes(): void
    {
        $filter = new TestDirectoryFilter(['tests'], ['tests/Fixtures', 'tests/Stubs']);

        self::assertFalse($filter->isIncluded($this->item('tests/Fixtures/Nested/Deep.php')));
        self::assertFalse($filter->isIncluded($this->item('tests/Stubs/Foo.php')));
        self::assertTrue($filter->isIncluded($this->item('tests/Unit/FooTest.php')));
    }

    #[Test]
    public function itFiltersAList(): void
    {
        $filter = new TestDirectoryFilter(['tests'], ['tests/Fixtures']);

        $items = [
            $this->item('tests/FooTest.php'),
            $this->item('tests/Fixtures/Bar.php'),
            $this->item('tests/BarTest.php'),
        ];

        $result = $filter->filter($items);

        self::assertCount(2, $result);
        self::assertSame('tests/FooTest.php', $result[0]->getRelativeFilePath());
        self::assertSame('tests/BarTest.php', $result[1]->getRelativeFilePath());
    }

    #[Test]
    public function itCreatesFromConfig(): void
    {
        $config = TestMapperConfig::create()
            ->testDirectories('tests', 'integration')
            ->excludeTestDirectories('tests/Fixtures')
            ->build();

        $filter = TestDirectoryFilter::fromConfig($config);

        self::assertTrue($filter->isIncluded($this->item('tests/FooTest.php')));
        self::assertTrue($filter->isIncluded($this->item('integration/BarTest.php')));
        self::assertFalse($filter->isIncluded($this->item('tests/Fixtures/Baz.php')));
    }

    #[Test]
    public function itReturnsTestDirectories(): void
    {
        $filter = new TestDirectoryFilter(['tests', 'integration'], []);

        self::assertSame(['tests', 'integration'], $filter->getTestDirectories());
    }

    #[Test]
    public function itReturnsDefaultTestDirectoriesWhenEmpty(): void
    {
        $filter = new TestDirectoryFilter([], []);

        self::assertSame(['tests'], $filter->getTestDirectories());
    }

    private function item(string $path): HasRelativeFilePath
    {
        return new class($path) implements HasRelativeFilePath {
            public function __construct(
                private readonly string $path,
            ) {
            }

            public function getRelativeFilePath(): string
            {
                return $this->path;
            }
        };
    }
}
