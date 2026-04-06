# Test directory filter

The `TestDirectoryFilter` decides whether a `HasRelativeFilePath` item (see `007-has-relative-file-path.md`) belongs in the configured test directories. It's used by both commands to scope their output to the right files.

## Interface

```
TestDirectoryFilter::isIncluded(HasRelativeFilePath $item): bool
TestDirectoryFilter::filter(array $items): array
TestDirectoryFilter::getTestDirectories(): string[]
```

The filter is constructed with two lists: `testDirectories` (include) and `excludeTestDirectories` (exclude).

## Defaults

If `testDirectories` is empty, it defaults to `['tests']`. If `excludeTestDirectories` is empty, nothing is excluded.

Both values come from `TestMapperConfig` (see `016-config-file.md`) via `TestDirectoryFilter::fromConfig()`, which can be overridden at the CLI level (see `017-cli-option-precedence.md`).

## Inclusion logic

A path is **included** if and only if:

1. It does **not** start with any entry in `excludeTestDirectories`, AND
2. It **does** start with any entry in `testDirectories`

Excludes are checked first. This means exclude always wins over include — if a path matches both, it's excluded.

## Boundary matching

Matching is done with a trailing slash to avoid partial name collisions:

- `tests/FooTest.php` → starts with `tests/` → **included** (under `tests` directory)
- `testing/Foo.php` → does not start with `tests/` → **not included**
- `tests/Fixtures/Bar.php` → starts with `tests/Fixtures/` → **excluded** if `tests/Fixtures` is in excludes
- `tests/FixturesExtra/Bar.php` → does not start with `tests/Fixtures/` → **not excluded** even if `tests/Fixtures` is in excludes

Without the trailing slash boundary, `tests` would also match `testing`, and `tests/Fixtures` would also match `tests/FixturesExtra`. The slash makes exact directory boundaries meaningful.

## Exact matches

A path equal to a directory name itself (without trailing content) is also considered inside that directory. This is a rare edge case but covered for consistency.

## Where it's applied

- `find-changed-tests` filters `ChangedTestMethod[]` after the diff pipeline (see `023-find-changed-tests-command.md`)
- `spec-reviewer` uses `getTestDirectories()` to decide which directories to recursively scan for PHP files when looking up tests by ticket (see `024-spec-reviewer-command.md`)
