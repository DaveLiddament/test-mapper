# Classified test

A `ClassifiedTest` is the output of the classifier: a single changed test assigned to one of four categories. A `TestClassificationResult` bundles them together with any orphaned specs and exposes the overall exit code.

## `ClassifiedTest`

Has:

- A `testName` — e.g. `App\Tests\AuthTest::itValidatesCredentials`
- A `status` — one of `TestStatus::Ok`, `TestStatus::NoTickets`, `TestStatus::UnexpectedChange`, or `TestStatus::NoTest`
- A list of `ticketIds` — the tickets declared on the test
- A list of `matchingSpecs` — the subset of `ticketIds` that correspond to changed specs

## `TestClassificationResult`

Has four lists, one per category:

- `ok` — tests whose tickets matched at least one changed spec
- `unexpectedChange` — tests that have tickets but none match any changed spec
- `noTickets` — tests with no tickets at all
- `noTest` — list of spec paths that no test references

See `014-test-classifier.md` for how tests are sorted into these lists.

## Exit code bitmask

`TestClassificationResult::getExitCode()` returns an integer bitmask:

| Bit | Value | Meaning |
|---|---|---|
| 0 | 1 | Tests with no tickets |
| 1 | 2 | Tests with unexpected changes |
| 2 | 4 | Specs with no test |

The bitmask is the sum of set bits. For example:

- Everything OK → `0`
- Only missing-ticket tests → `1`
- Missing tickets + unexpected change → `1 + 2 = 3`
- All three problems → `1 + 2 + 4 = 7`

This lets CI distinguish between problem types using a single integer return code. See `023-find-changed-tests-command.md`.
