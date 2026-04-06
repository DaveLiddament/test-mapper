# HasRelativeFilePath interface

`HasRelativeFilePath` is a single-method interface implemented by any model object that carries a file path. It's the contract used by `TestDirectoryFilter` (see `015-test-directory-filter.md`) to decide whether an item belongs to a configured test directory.

## Interface

```
HasRelativeFilePath::getRelativeFilePath(): string
```

The returned path is:

- **Relative** to the project root (not absolute)
- Separated with forward slashes regardless of platform
- The same form git uses in `--name-status` and unified diff headers (see `008-git-diff-provider.md`)

## Implementations

- `TestMethod` (see `002-test-method.md`) — used by `spec-reviewer` when scanning test directories
- `ChangedTestMethod` (see `001-changed-test-method.md`) — used by `find-changed-tests` after the diff pipeline

Both commands filter their results through `TestDirectoryFilter::filter()`, which accepts any list of `HasRelativeFilePath` implementations and returns the subset inside the configured directories.

## Why relative paths

Using relative paths throughout the system has three benefits:

1. **Portability** — the same result works regardless of where the repo is cloned
2. **Git compatibility** — all git commands used by the tool (`git diff`, `git ls-files`) emit relative paths, so no conversion is needed
3. **Config alignment** — `testDirectories` and `excludeTestDirectories` in the config file are specified as relative paths (`tests`, `tests/Fixtures`), matching what the model objects carry

Absolute paths would require normalisation at every boundary, with subtle bugs around symlinks and case sensitivity. Relative throughout avoids the problem entirely.
