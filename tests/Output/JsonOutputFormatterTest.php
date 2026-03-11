<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Output;

use DaveLiddament\TestMapper\Model\ChangedSpecFile;
use DaveLiddament\TestMapper\Model\ChangedTestMethod;
use DaveLiddament\TestMapper\Model\FileChangeType;
use DaveLiddament\TestMapper\Output\JsonOutputFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

#[CoversClass(JsonOutputFormatter::class)]
final class JsonOutputFormatterTest extends TestCase
{
    private JsonOutputFormatter $formatter;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->formatter = new JsonOutputFormatter();
        $this->output = new BufferedOutput();
    }

    #[Test]
    public function itOutputsJsonWithTestsAndSpecs(): void
    {
        $this->formatter->format(
            [new ChangedTestMethod('App\\Tests\\FooTest', 'it_works', ['JIRA-123'])],
            [new ChangedSpecFile(FileChangeType::Added, 'auth/login.md')],
            $this->output,
        );

        $data = json_decode($this->output->fetch(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertCount(1, $data['tests']);
        self::assertSame('App\\Tests\\FooTest::it_works', $data['tests'][0]['name']);
        self::assertSame(['JIRA-123'], $data['tests'][0]['ticketIds']);

        self::assertCount(1, $data['specs']);
        self::assertSame('added', $data['specs'][0]['changeType']);
        self::assertSame('auth/login.md', $data['specs'][0]['filePath']);
    }

    #[Test]
    public function itOutputsEmptyArraysWhenNoChanges(): void
    {
        $this->formatter->format([], [], $this->output);

        $data = json_decode($this->output->fetch(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame([], $data['tests']);
        self::assertSame([], $data['specs']);
    }

    #[Test]
    public function itOutputsMultipleTests(): void
    {
        $this->formatter->format(
            [
                new ChangedTestMethod('App\\Tests\\FooTest', 'it_works', ['JIRA-1']),
                new ChangedTestMethod('App\\Tests\\BarTest', 'it_also_works'),
            ],
            [],
            $this->output,
        );

        $data = json_decode($this->output->fetch(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertCount(2, $data['tests']);
        self::assertSame('App\\Tests\\FooTest::it_works', $data['tests'][0]['name']);
        self::assertSame(['JIRA-1'], $data['tests'][0]['ticketIds']);
        self::assertSame('App\\Tests\\BarTest::it_also_works', $data['tests'][1]['name']);
        self::assertSame([], $data['tests'][1]['ticketIds']);
    }
}
