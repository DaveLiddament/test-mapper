# Name-status parser

The `NameStatusDiffParser` reads the output of `git diff --name-status` and produces a list of `ChangedSpecFile` entries (see `005-changed-spec-file.md`). It's the spec-side equivalent of the unified diff parser.

## Format

`git diff --name-status` produces tab-separated lines like:

```
M	specs/auth/login.md
A	specs/auth/session.md
D	specs/old-feature.md
R100	specs/old-name.md	specs/new-name.md
```

The first field is a status letter (optionally followed by a similarity percentage for renames and copies). The remaining field(s) are file paths.

## Interface

```
NameStatusDiffParser::parse(string $output, string $specsDirPrefix): ChangedSpecFile[]
```

The `$specsDirPrefix` argument is stripped from every path before the result is built. File extensions are also stripped, so `specs/auth/login.md` becomes the canonical ticket identifier `auth/login`.

## Status letter mapping

See `005-changed-spec-file.md` for the full table. In summary:

- `A`, `M`, `D` map directly
- `T` (type change) is treated as `Modified`
- `R` (rename) produces two entries: a `Deleted` for the old path and an `Added` for the new path
- `C` (copy) produces a single `Added` for the destination; the source is ignored
- Unknown letters are silently skipped

## Edge cases

- **No tab separator** — lines without a tab are skipped silently
- **Empty or whitespace-only lines** — skipped
- **Rename without a destination field** — no output is produced
- **Path not under the prefix** — the path is kept as-is without stripping
- **Prefix with or without trailing slash** — handled identically

## Why strip the prefix and extension

The canonical identifier for a spec (`auth/login`) is what tests reference via `#[Ticket('auth/login')]` (see `026-ticket-convention.md`). The parser strips the boilerplate so the classifier (see `014-test-classifier.md`) can match spec files to tests with a simple string comparison.
