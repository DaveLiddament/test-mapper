# Test method

A `TestMethod` is the output of the PHPUnit AST parser: a rich description of a single test method with everything needed to decide whether it was affected by a change.

## Structure

A `TestMethod` has:

- A `fullyQualifiedClassName` — the namespace-qualified class name
- A `methodName`
- A `startLine` and `endLine` — the line range of the method, including its docblock and attributes (not just the `function` keyword)
- A `filePath` — relative to the project root
- A list of `dependentRanges` — zero or more `LineRange` entries pointing to `#[DataProvider]` methods referenced by this test (see `003-line-range.md`)
- A list of `ticketIds` — from `#[Ticket]` attributes at either class or method level

## Start line rule

`startLine` is the earliest line of any of:

- The method's docblock (if present)
- The method's first `#[...]` attribute
- The `function` keyword itself

This matters for change detection: if a developer modifies only a docblock or adds an attribute, the method still counts as changed (see `013-changed-test-method-finder.md`).

## Relationship to `ChangedTestMethod`

`TestMethod` is the raw parser output. `ChangedTestMethodFinder` consumes it to produce `ChangedTestMethod` (see `001-changed-test-method.md`) — a pared-down value object that drops the line-range metadata once the overlap check is complete.

`TestMethod` implements `HasRelativeFilePath` (see `007-has-relative-file-path.md`) so it can be filtered by `TestDirectoryFilter` in `spec-reviewer` (see `024-spec-reviewer-command.md`) which reads `TestMethod` directly rather than going through the diff pipeline.
