# Changed spec file

A `ChangedSpecFile` represents a single spec file that has changed, with its path and change type.

## Structure

A `ChangedSpecFile` has:

- A `changeType` — one of `FileChangeType::Added`, `FileChangeType::Modified`, or `FileChangeType::Deleted`
- A `filePath` — the spec path with the specs directory prefix and file extension stripped. For example, `specs/auth/login.md` becomes `auth/login`

The stripped form is the canonical identifier used to match tests' `#[Ticket]` values (see `026-ticket-convention.md`).

## How git statuses map to `FileChangeType`

Git's `--name-status` output uses letter codes. The `NameStatusDiffParser` (see `010-name-status-parser.md`) maps them as follows:

| Git status | Result |
|---|---|
| `A` (added) | `FileChangeType::Added` |
| `M` (modified) | `FileChangeType::Modified` |
| `D` (deleted) | `FileChangeType::Deleted` |
| `R` (renamed) | emitted as two entries: `Deleted` (old path) + `Added` (new path) |
| `C` (copied) | emitted as `Added` for the new path; source is ignored |
| `T` (type change) | `FileChangeType::Modified` |
| Unknown letters | silently skipped |

Renames are split into delete + add so that tests referencing the old ticket ID are flagged as `NoTest` (see `014-test-classifier.md`) and tests referencing the new ticket ID are classified normally.

## Consumed by `TestClassifier`

`ChangedSpecFile` entries are fed into the classifier (see `014-test-classifier.md`) alongside the list of changed tests. The classifier matches each test's ticket IDs against the changed spec file paths to produce a `ClassifiedTest` for each test and flag any spec that no test covers.
