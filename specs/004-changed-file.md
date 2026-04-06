# Changed file

A `ChangedFile` holds everything known about a single file that has been changed in a diff.

## Structure

A `ChangedFile` has:

- A relative `filePath` — the path as reported by git, relative to the repo root
- A list of `ChangedLineRange` entries (see `003-line-range.md`) — the line ranges that were added or modified

## Interface

```
ChangedFile::overlapsRange(int $startLine, int $endLine): bool
```

Returns `true` if any of the file's `ChangedLineRange` entries overlap with the given range. This is the primary query used by `ChangedTestMethodFinder` (see `013-changed-test-method-finder.md`) to decide whether a given test method was affected.

## Empty line range list

A `ChangedFile` with no line ranges never overlaps. This happens for:

- Files from unified diffs where every hunk was deletion-only (see `003-line-range.md` for the deletion-only rule)
- Files from malformed hunks that the parser skipped

In both cases the file still appears in the list (so callers can see it was touched) but the overlap check returns `false` for any range.

## Special case: untracked files

When `--include-untracked` is passed, untracked files are represented as `ChangedFile` entries with a single `ChangedLineRange` spanning `(1, PHP_INT_MAX)` — effectively "the entire file is new". This ensures any test method inside an untracked file overlaps and is picked up. See `008-git-diff-provider.md`.
