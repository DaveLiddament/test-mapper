# Changes to Review

## Contents

### Specs

- [019-json-output-format](#019-json-output-format) ([view file](specs/019-json-output-format.md))

### Tests

- [DaveLiddament\TestMapper\Tests\Output\JsonOutputFormatterTest::legacyFormatOutputsTests](#daveliddamenttestmappertestsoutputjsonoutputformattertestlegacyformatoutputstests) ([view file](tests/Output/JsonOutputFormatterTest.php))
- [DaveLiddament\TestMapper\Tests\Output\JsonOutputFormatterTest::legacyFormatOutputsEmptyArraysWhenNoChanges](#daveliddamenttestmappertestsoutputjsonoutputformattertestlegacyformatoutputsemptyarrayswhennochanges) ([view file](tests/Output/JsonOutputFormatterTest.php))
- [DaveLiddament\TestMapper\Tests\Output\JsonOutputFormatterTest::legacyFormatOutputsMultipleTests](#daveliddamenttestmappertestsoutputjsonoutputformattertestlegacyformatoutputsmultipletests) ([view file](tests/Output/JsonOutputFormatterTest.php))
- [DaveLiddament\TestMapper\Tests\Output\JsonOutputFormatterTest::classifiedFormatOutputsGroupedJson](#daveliddamenttestmappertestsoutputjsonoutputformattertestclassifiedformatoutputsgroupedjson) ([view file](tests/Output/JsonOutputFormatterTest.php))
- [DaveLiddament\TestMapper\Tests\Output\JsonOutputFormatterTest::classifiedFormatOutputsEmptyGroups](#daveliddamenttestmappertestsoutputjsonoutputformattertestclassifiedformatoutputsemptygroups) ([view file](tests/Output/JsonOutputFormatterTest.php))

---

## Specs

### 019-json-output-format

`specs/019-json-output-format.md`

# JSON output format

The `json` format emits a single pretty-printed JSON document. It's the machine-readable format intended for tooling, dashboards, and scripted post-processing.

## Two shapes

Like the table formatter (see `018-table-output-format.md`), the JSON formatter has legacy and classified modes depending on whether `--specs-dir` was provided.

## Legacy shape (no specs)

```json
{
    "tests": [
        {
            "name": "App\\Tests\\FooTest::it_works",
            "ticketIds": ["JIRA-123"]
        },
        {
            "name": "App\\Tests\\BarTest::it_also_works",
            "ticketIds": []
        }
    ]
}
```

- `tests` — always present, possibly empty
- Each entry has `name` (the fully qualified test name) and `ticketIds` (empty array if none)

## Classified shape (with specs)

```json
{
    "noTest": ["auth/login"],
    "unexpectedChange": [
        {
            "test": "App\\Tests\\FooTest::bar",
            "tickets": ["JIRA-1"],
            "matchingSpecs": []
        }
    ],
    "noTickets": [
        {
            "test": "App\\Tests\\BazTest::qux",
            "tickets": [],
            "matchingSpecs": []
        }
    ],
    "ok": [
        {
            "test": "App\\Tests\\BarTest::foo",
            "tickets": ["auth/login"],
            "matchingSpecs": ["auth/login"]
        }
    ]
}
```

All four keys are always present, even when empty. `noTest` is a flat array of spec paths; the other three are arrays of objects with `test`, `tickets`, and `matchingSpecs`.

## Formatting

The output uses `JSON_PRETTY_PRINT` and `JSON_THROW_ON_ERROR`. Pretty printing makes the output human-readable enough to diff in a PR, while still being valid JSON for consumers.

## Consumers

- CI dashboards that want structured data
- Scripts that transform the result into other formats
- Debugging when the table format hides detail you need

For line-level GitHub PR annotations, use the `github` format (see `021-github-output-format.md`). For piping into `spec-reviewer`, use the `specs` format (see `020-specs-output-format.md`).

---

## Tests

### 019-json-output-format

#### DaveLiddament\TestMapper\Tests\Output\JsonOutputFormatterTest::legacyFormatOutputsTests

`tests/Output/JsonOutputFormatterTest.php`

```php
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
```

#### DaveLiddament\TestMapper\Tests\Output\JsonOutputFormatterTest::legacyFormatOutputsEmptyArraysWhenNoChanges

`tests/Output/JsonOutputFormatterTest.php`

```php
    #[Test]
    public function legacyFormatOutputsEmptyArraysWhenNoChanges(): void
    {
        $this->formatter->format([], null, $this->output);

        $data = json_decode($this->output->fetch(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame([], $data['tests']);
    }
```

#### DaveLiddament\TestMapper\Tests\Output\JsonOutputFormatterTest::legacyFormatOutputsMultipleTests

`tests/Output/JsonOutputFormatterTest.php`

```php
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
```

#### DaveLiddament\TestMapper\Tests\Output\JsonOutputFormatterTest::classifiedFormatOutputsGroupedJson

`tests/Output/JsonOutputFormatterTest.php`

```php
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
```

#### DaveLiddament\TestMapper\Tests\Output\JsonOutputFormatterTest::classifiedFormatOutputsEmptyGroups

`tests/Output/JsonOutputFormatterTest.php`

```php
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
```

---

