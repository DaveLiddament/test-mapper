# End-to-end

A walkthrough of the full `find-changed-tests` pipeline from a branch change to a classified result. Each stage references the relevant spec for details.

## The scenario

You're on a feature branch. You've changed `specs/auth/login.md` and updated a test method in `tests/AuthTest.php` that carries `#[Ticket('auth/login')]`. You run:

```
./vendor/bin/test-mapper --specs-dir specs
```

## Stage 1 — Load config

`ConfigLoader` looks for `.test-mapper.php` (see `016-config-file.md`). If it exists, its values seed the resolved options; CLI options override them (see `017-cli-option-precedence.md`).

## Stage 2 — Find changed test files

`GitDiffProvider` (see `008-git-diff-provider.md`) runs:

```
git diff main --unified=0 --no-color --no-ext-diff
```

It passes the output to `UnifiedDiffParser` (see `009-unified-diff-parser.md`), which produces a list of `ChangedFile` (see `004-changed-file.md`) with `ChangedLineRange` entries (see `003-line-range.md`). One entry points at `tests/AuthTest.php` with the lines you touched.

## Stage 3 — Parse test files

`ChangedTestMethodFinder` (see `013-changed-test-method-finder.md`) iterates the changed files. For `tests/AuthTest.php`, it calls `PhpUnitTestMethodFinder` (see `011-phpunit-test-method-finder.md`), which parses the file and returns a list of `TestMethod` objects (see `002-test-method.md`), one per `#[Test]` method in the file.

For each test, the finder checks whether any of the file's changed line ranges overlap with the test's `[startLine, endLine]` (or any dependent range). The `itValidatesCredentials` method overlaps, so a `ChangedTestMethod` is emitted (see `001-changed-test-method.md`) with `ticketIds = ['auth/login']`.

## Stage 4 — Filter by test directory

`TestDirectoryFilter` (see `015-test-directory-filter.md`) drops anything not under `tests/`. In this scenario the result passes through unchanged.

## Stage 5 — Find changed specs

Because `--specs-dir specs` was provided, `GitChangedSpecsFinder` runs:

```
git diff --name-status main -- specs
```

`NameStatusDiffParser` (see `010-name-status-parser.md`) turns this into a `ChangedSpecFile` list. Your modification to `specs/auth/login.md` produces `ChangedSpecFile(Modified, 'auth/login')` (see `005-changed-spec-file.md`).

## Stage 6 — Classify

`TestClassifier` (see `014-test-classifier.md`) sees:

- One changed test with `ticketIds = ['auth/login']`
- One changed spec with path `auth/login`

The test's ticket intersects the changed spec → the test is `ok` with `matchingSpecs = ['auth/login']`. The spec is covered → not in `noTest`. Result: one `ok`, nothing in the problem categories, exit code `0` (see `006-classified-test.md`).

## Stage 7 — Format

The `table` formatter (see `018-table-output-format.md`) renders:

| Test | Tickets | Specs | Status |
|---|---|---|---|
| `App\Tests\AuthTest::itValidatesCredentials` | auth/login | auth/login | OK |

## Stage 8 — Exit

`TestClassificationResult::getExitCode()` returns `0`. The process exits cleanly and CI passes.

## What about the bad cases

- **Missing ticket** — the test has no `#[Ticket]`, it would be classified as `NoTickets` and the exit code would be `1`
- **Ticket without matching spec** — the test references `auth/session` but only `auth/login` was changed, the test is `UnexpectedChange`, exit code `2`
- **Unreferenced spec** — `specs/auth/session.md` also changed but no test references it, the spec is in `noTest`, exit code `4`
- **All three at once** — exit code `7` (1 + 2 + 4)

## The two-command pipeline

For AI-assisted review, the full pipeline extends to:

```
test-mapper --specs-dir specs --format specs \
  | spec-reviewer --specs-dir specs > review.md
```

The first command emits only OK spec names (see `020-specs-output-format.md`). The second reads them and produces the markdown review document (see `022-ai-review-markdown-format.md`, `024-spec-reviewer-command.md`) bundling spec contents and test source code together for an AI to review.
