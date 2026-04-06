# Changed test method finder

The `ChangedTestMethodFinder` is the heart of `find-changed-tests`. It combines the diff output from `GitDiffProvider` with the test method list from `PhpUnitTestMethodFinder` to identify which tests were affected by a change.

## Algorithm

```
for each changed file from the git diff:
    if the file is not a .php file, skip
    parse the file to get all TestMethod objects
    for each TestMethod:
        if the file's changed lines overlap with the method's [startLine, endLine]:
            emit a ChangedTestMethod and continue to the next method
        else:
            for each dependent range (data provider):
                if the changed lines overlap with that range:
                    emit a ChangedTestMethod and break out of the inner loop
```

This means a test counts as changed when *any* of these overlap with a changed line range:

- The method body
- The method's docblock (included in `startLine`)
- The method's attributes (included in `startLine`)
- Any `#[DataProvider]` method the test references (via `dependentRanges`)

## Why data provider ranges matter

If a developer changes the data provider, the test's behaviour changes even though the test method itself is untouched. Tracking dependent ranges ensures those tests are flagged.

## Deduplication

Each `TestMethod` produces **at most one** `ChangedTestMethod`. The algorithm uses `continue` after emitting from a body overlap, and `break` after emitting from a dependent range match, so a test that overlaps both conditions only appears once in the result.

## File filtering

Non-PHP files are skipped entirely. Files from the diff that happen to be PHP but contain no test methods (e.g. `src/` code) produce no output. The downstream `TestDirectoryFilter` (see `015-test-directory-filter.md`) further trims results to the configured test directories.

## Inputs and outputs

- **Input**: a git ref to compare against, the include-untracked flag
- **Output**: a list of `ChangedTestMethod` (see `001-changed-test-method.md`), one per affected test

## Composition

```
GitDiffProvider → UnifiedDiffParser → [ChangedFile]
PhpUnitTestMethodFinder → [TestMethod]

ChangedTestMethodFinder combines these into [ChangedTestMethod]
```

The `find-changed-tests` command (see `023-find-changed-tests-command.md`) then passes the result to the classifier and the selected output formatter.
