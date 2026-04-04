<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Output;

use DaveLiddament\TestMapper\Model\ChangedTestMethod;
use DaveLiddament\TestMapper\Model\ClassifiedTest;
use DaveLiddament\TestMapper\Model\TestClassificationResult;
use DaveLiddament\TestMapper\Model\TestStatus;
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
    public function legacyFormatOutputsTests(): void
    {
        $this->formatter->format(
            [new ChangedTestMethod('App\\Tests\\FooTest', 'it_works', ['JIRA-123'])],
            null,
            $this->output,
        );

        $data = json_decode($this->output->fetch(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertCount(1, $data['tests']);
        self::assertSame('App\\Tests\\FooTest::it_works', $data['tests'][0]['name']);
        self::assertSame(['JIRA-123'], $data['tests'][0]['ticketIds']);
    }

    #[Test]
    public function legacyFormatOutputsEmptyArraysWhenNoChanges(): void
    {
        $this->formatter->format([], null, $this->output);

        $data = json_decode($this->output->fetch(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame([], $data['tests']);
    }

    #[Test]
    public function legacyFormatOutputsMultipleTests(): void
    {
        $this->formatter->format(
            [
                new ChangedTestMethod('App\\Tests\\FooTest', 'it_works', ['JIRA-1']),
                new ChangedTestMethod('App\\Tests\\BarTest', 'it_also_works'),
            ],
            null,
            $this->output,
        );

        $data = json_decode($this->output->fetch(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertCount(2, $data['tests']);
        self::assertSame('App\\Tests\\FooTest::it_works', $data['tests'][0]['name']);
        self::assertSame(['JIRA-1'], $data['tests'][0]['ticketIds']);
        self::assertSame('App\\Tests\\BarTest::it_also_works', $data['tests'][1]['name']);
        self::assertSame([], $data['tests'][1]['ticketIds']);
    }

    #[Test]
    public function classifiedFormatOutputsGroupedJson(): void
    {
        $result = new TestClassificationResult(
            noTest: ['auth/login'],
            unexpectedChange: [new ClassifiedTest('App\\Tests\\FooTest::bar', TestStatus::UnexpectedChange, ['JIRA-1'], [])],
            noTickets: [new ClassifiedTest('App\\Tests\\BazTest::qux', TestStatus::NoTickets, [], [])],
            ok: [new ClassifiedTest('App\\Tests\\BarTest::foo', TestStatus::Ok, ['auth/login'], ['auth/login'])],
        );

        $this->formatter->format([], $result, $this->output);

        $data = json_decode($this->output->fetch(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame(['auth/login'], $data['noTest']);

        self::assertCount(1, $data['unexpectedChange']);
        self::assertSame('App\\Tests\\FooTest::bar', $data['unexpectedChange'][0]['test']);
        self::assertSame(['JIRA-1'], $data['unexpectedChange'][0]['tickets']);
        self::assertSame([], $data['unexpectedChange'][0]['matchingSpecs']);

        self::assertCount(1, $data['noTickets']);
        self::assertSame('App\\Tests\\BazTest::qux', $data['noTickets'][0]['test']);
        self::assertSame([], $data['noTickets'][0]['tickets']);
        self::assertSame([], $data['noTickets'][0]['matchingSpecs']);

        self::assertCount(1, $data['ok']);
        self::assertSame('App\\Tests\\BarTest::foo', $data['ok'][0]['test']);
        self::assertSame(['auth/login'], $data['ok'][0]['tickets']);
        self::assertSame(['auth/login'], $data['ok'][0]['matchingSpecs']);
    }

    #[Test]
    public function classifiedFormatOutputsEmptyGroups(): void
    {
        $result = new TestClassificationResult([], [], [], []);

        $this->formatter->format([], $result, $this->output);

        $data = json_decode($this->output->fetch(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame([], $data['noTest']);
        self::assertSame([], $data['unexpectedChange']);
        self::assertSame([], $data['noTickets']);
        self::assertSame([], $data['ok']);
    }
}
