# find-changed-tests command

The `find-changed-tests` command is the main binary of test-mapper. It diffs the current state against a target branch, identifies changed tests, classifies them against changed specs (if provided), and writes the result via the chosen output formatter.

## Usage

```
./vendor/bin/test-mapper [options]
```

## Options

| Option | Short | Default | Description |
|---|---|---|---|
| `--branch` | `-b` | `main` | Base branch to diff against |
| `--specs-dir` | `-d` | _(none)_ | Specs directory (enables classification) |
| `--format` | `-f` | `table` | Output format: `table`, `json`, `specs`, or `github` |
| `--include-untracked` | `-u` | off | Include untracked files in the diff |
| `--test-dir` | `-t` | `tests` | Test directory to scan (repeatable) |
| `--exclude-test-dir` | `-e` | _(none)_ | Test directory to exclude (repeatable) |
| `--config` | `-c` | `.test-mapper.php` | Path to config file |
| `--output` | `-o` | stdout | Write output to file instead of stdout |

## Execution pipeline

1. Load the config file (see `016-config-file.md`), merge with CLI options (see `017-cli-option-precedence.md`)
2. Validate that `--specs-dir`, if provided, points to an existing directory — error with exit code 1 if not
3. Build a `TestDirectoryFilter` (see `015-test-directory-filter.md`) from the resolved directory lists
4. Run `ChangedTestMethodFinder` (see `013-changed-test-method-finder.md`) to get the raw changed tests, then filter through the test directory filter
5. If `--specs-dir` was provided, run `GitChangedSpecsFinder` to get changed spec files, then classify both lists with `TestClassifier` (see `014-test-classifier.md`)
6. Resolve the output target (stdout or `--output` file) and the formatter (see `018`–`021`)
7. Call the formatter
8. Return the classification exit code (see `006-classified-test.md`), or `0` if no classification ran

## Exit codes

| Code | Meaning |
|---|---|
| `0` | Success (everything OK, or no classification ran) |
| `1` | Problem bit set: tests with no tickets |
| `2` | Problem bit set: unexpected changes |
| `4` | Problem bit set: specs with no test |
| `1`–`7` | Combination of the above (see `006-classified-test.md`) |
| `1` | Also returned on CLI error (missing specs dir, bad config file) — distinguishable by the error message on stderr |

## Errors

- **Missing `--specs-dir`** — "Specs directory not found: <path>", exit 1
- **Missing `--config` file** — "Config file not found: <path>", exit 1
- **Config file returns wrong type** — "Config file must return an instance of TestMapperConfig", exit 1
- **Unknown format** — falls back to `table` silently
- **Git failure** — `DiffException` propagates with the underlying git error

## No `--specs-dir` mode

When `--specs-dir` is omitted, classification is skipped entirely. The formatter receives `null` for the classification result and falls back to its legacy mode (see individual format specs). Exit code is always `0`.
