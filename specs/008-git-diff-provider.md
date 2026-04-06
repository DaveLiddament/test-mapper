# Git diff provider

The `GitDiffProvider` runs git commands to determine which files (and which lines within them) have changed since a given ref. It's the entry point into the change-detection pipeline.

## Interface

```
GitDiffProvider::getChangedFiles(string $compareTo, bool $includeUntracked): ChangedFile[]
```

- `$compareTo` — the branch or ref to diff against, e.g. `main` or `HEAD`
- `$includeUntracked` — if `true`, also include untracked files that git doesn't track at all yet

Returns a list of `ChangedFile` (see `004-changed-file.md`).

## Git commands

For the main diff:

```
git diff <compareTo> --unified=0 --no-color --no-ext-diff
```

- `--unified=0` — no context lines, only the actual changes
- `--no-color` — ensure clean output regardless of the user's git config
- `--no-ext-diff` — bypass any external diff tools the user may have configured

The output is piped into the `UnifiedDiffParser` (see `009-unified-diff-parser.md`) which produces the `ChangedFile` list.

## Untracked files

When `$includeUntracked` is true, an additional command runs:

```
git ls-files --others --exclude-standard
```

This lists files that are not tracked by git and not covered by `.gitignore`. Each one is appended to the result as a `ChangedFile` with a single `ChangedLineRange(1, PHP_INT_MAX)`, meaning "the entire file is new". Empty lines and whitespace in the output are skipped.

The two result lists are concatenated: tracked diffs first, then untracked files.

## Error handling

If the git command fails (e.g. invalid branch, not a git repo), the underlying `ProcessFailedException` is caught and rethrown as `DiffException` with a message identifying the failure. Untracked file listing uses the same pattern.

## Working directory

The provider is constructed with an explicit `workingDirectory` path so all git commands run in the right place regardless of where PHP was invoked from.
