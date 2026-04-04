<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Output;

use DaveLiddament\TestMapper\Model\ClassifiedTest;
use DaveLiddament\TestMapper\Model\TestClassificationResult;
use DaveLiddament\TestMapper\Model\TestStatus;
use DaveLiddament\TestMapper\Output\SpecsOutputFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

#[CoversClass(SpecsOutputFormatter::class)]
final class SpecsOutputFormatterTest extends TestCase
{
    private SpecsOutputFormatter $formatter;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->formatter = new SpecsOutputFormatter();
        $this->output = new BufferedOutput();
    }

    #[Test]
    public function itOutputsOkSpecNames(): void
    {
        $result = new TestClassificationResult(
            noTest: [],
            unexpectedChange: [],
            noTickets: [],
            ok: [
                new ClassifiedTest('App\\Tests\\FooTest::bar', TestStatus::Ok, ['auth/login'], ['auth/login']),
                new ClassifiedTest('App\\Tests\\BazTest::qux', TestStatus::Ok, ['auth/session'], ['auth/session']),
            ],
        );

        $this->formatter->format([], $result, $this->output);

        self::assertSame("auth/login\nauth/session\n", $this->output->fetch());
    }

    #[Test]
    public function itDeduplicatesSpecs(): void
    {
        $result = new TestClassificationResult(
            noTest: [],
            unexpectedChange: [],
            noTickets: [],
            ok: [
                new ClassifiedTest('App\\Tests\\FooTest::bar', TestStatus::Ok, ['auth/login'], ['auth/login']),
                new ClassifiedTest('App\\Tests\\BazTest::qux', TestStatus::Ok, ['auth/login'], ['auth/login']),
            ],
        );

        $this->formatter->format([], $result, $this->output);

        self::assertSame("auth/login\n", $this->output->fetch());
    }

    #[Test]
    public function itSortsSpecsAlphabetically(): void
    {
        $result = new TestClassificationResult(
            noTest: [],
            unexpectedChange: [],
            noTickets: [],
            ok: [
                new ClassifiedTest('App\\Tests\\FooTest::bar', TestStatus::Ok, ['z-spec'], ['z-spec']),
                new ClassifiedTest('App\\Tests\\BazTest::qux', TestStatus::Ok, ['a-spec'], ['a-spec']),
            ],
        );

        $this->formatter->format([], $result, $this->output);

        self::assertSame("a-spec\nz-spec\n", $this->output->fetch());
    }

    #[Test]
    public function itOutputsNothingWhenNoClassificationResult(): void
    {
        $this->formatter->format([], null, $this->output);

        self::assertSame('', $this->output->fetch());
    }

    #[Test]
    public function itOutputsNothingWhenNoOkTests(): void
    {
        $result = new TestClassificationResult(
            noTest: ['auth/login'],
            unexpectedChange: [],
            noTickets: [],
            ok: [],
        );

        $this->formatter->format([], $result, $this->output);

        self::assertSame('', $this->output->fetch());
    }
}
