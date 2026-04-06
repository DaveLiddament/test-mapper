# Changed test method

A `ChangedTestMethod` is the output of the change-detection pipeline: a test that has been identified as affected by a git diff and needs attention.

## Structure

A `ChangedTestMethod` has:

- A `fullyQualifiedClassName` — the namespace-qualified class name, e.g. `App\Tests\AuthTest`
- A `methodName` — the method name, e.g. `itValidatesCredentials`
- A list of `ticketIds` — zero or more ticket identifiers from `#[Ticket]` attributes (see `011-phpunit-test-method-finder.md`)
- A relative `filePath` — where the test lives in the repository (see `007-has-relative-file-path.md`)

## Interface

```
$method->getFullyQualifiedName() // "App\\Tests\\AuthTest::itValidatesCredentials"
$method->getRelativeFilePath()   // "tests/AuthTest.php"
```

`getFullyQualifiedName()` concatenates the class name and method name with `::`. This is the canonical identifier used throughout the output formatters (see `018-table-output-format.md`, `019-json-output-format.md`, `021-github-output-format.md`).

## Relationship to `TestMethod`

`ChangedTestMethod` is a lightweight projection of `TestMethod` (see `002-test-method.md`). The parser emits `TestMethod` objects with full AST metadata (start/end lines, dependent ranges); the `ChangedTestMethodFinder` (see `013-changed-test-method-finder.md`) filters those down to a list of `ChangedTestMethod` objects, dropping the line metadata that's no longer needed.

`ChangedTestMethod` implements `HasRelativeFilePath` so it can be filtered by `TestDirectoryFilter` (see `015-test-directory-filter.md`).
