# Test classifier

The `TestClassifier` takes a list of changed tests and a list of changed spec files, and produces a `TestClassificationResult` (see `006-classified-test.md`) that sorts every test and spec into one of four buckets.

## Interface

```
TestClassifier::classify(ChangedTestMethod[] $tests, ChangedSpecFile[] $specs): TestClassificationResult
```

## Algorithm

Build a set of changed spec file paths (already stripped to canonical form by the parser — see `010-name-status-parser.md`).

For each changed test, look at its `ticketIds`:

- If `ticketIds` is empty → `noTickets`
- Otherwise, compute the intersection of `ticketIds` and the changed-spec set
- If the intersection is non-empty → `ok`, with the intersection as `matchingSpecs`
- If the intersection is empty → `unexpectedChange`

For the spec side: any spec that appears in the changed list but isn't referenced by any `ok` test goes into `noTest`.

## The four categories

| Category | Meaning | Bit |
|---|---|---|
| `ok` | Test has a ticket that matches a changed spec | — |
| `noTickets` | Test changed but has no ticket attributes at all | 1 |
| `unexpectedChange` | Test has tickets but none match any changed spec | 2 |
| `noTest` | A spec changed but no test references it | 4 |

## Sorting

Results within each category are sorted alphabetically by test name (for test categories) or spec path (for `noTest`). This makes the output deterministic and easy to diff across CI runs.

## Exit code

`TestClassificationResult::getExitCode()` returns the bitmask of set problem bits (see `006-classified-test.md`). An `ok`-only result returns `0`; any combination of problems returns the corresponding sum (1–7).

## Why these four categories

They form a complete taxonomy of "what can be wrong" when aligning tests and specs:

- **OK** — the desired state
- **NoTickets** — the developer forgot to tag the test
- **UnexpectedChange** — the test changed but nothing in the corresponding spec did, which usually means the spec should be updated too
- **NoTest** — the spec changed but no test covers it, meaning the change is untested

Any real PR that touches behaviour should produce either `OK` or a clear signal in one of the three problem categories.
