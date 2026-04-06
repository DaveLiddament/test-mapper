# spec-reviewer command

The `spec-reviewer` command takes a list of spec identifiers and produces a self-contained markdown document bundling each spec's contents with the source code of every matching test. The document is designed for AI-assisted code review (see `022-ai-review-markdown-format.md`).

## Usage

```
./vendor/bin/spec-reviewer [options] [spec ...]
```

Spec names are passed as positional arguments. If no arguments are given, spec names are read from stdin (one per line), which enables piping from `test-mapper --format specs` (see `020-specs-output-format.md`):

```
./vendor/bin/test-mapper --specs-dir specs --format specs \
  | ./vendor/bin/spec-reviewer --specs-dir specs > review.md
```

## Options

| Option | Short | Default | Description |
|---|---|---|---|
| `--specs-dir` | `-d` | _(required)_ | Specs directory |
| `--no-specs` | | off | Omit the Specs section from output |
| `--test-dir` | `-t` | `tests` | Test directory to scan (repeatable) |
| `--exclude-test-dir` | `-e` | _(none)_ | Test directory to exclude (repeatable) |
| `--config` | `-c` | `.test-mapper.php` | Path to config file |
| `--output` | `-o` | stdout | Write markdown to file instead of stdout |

## Execution pipeline

1. Load the config file, merge with CLI options
2. Validate that `--specs-dir` was supplied (via CLI or config) and points to an existing directory — error if not
3. Collect spec names from the positional args or stdin
4. Recursively scan each `testDirectories` entry for `.php` files
5. For each file, run `PhpUnitTestMethodFinder` (see `011-phpunit-test-method-finder.md`) to get all test methods
6. Filter tests to those whose ticket IDs match any of the requested specs
7. Build a map: spec name → list of matching tests
8. Render the markdown document (see `022-ai-review-markdown-format.md`) via `SourceCodeReader` (see `012-source-code-reader.md`)
9. Write to stdout or `--output` file

## Errors

- **Missing `--specs-dir`** — "The --specs-dir option is required", exit 1
- **`--specs-dir` does not exist** — "Specs directory not found: <path>", exit 1
- **No spec names provided** — "No spec names provided", exit 1
- **Missing `--config` file** — "Config file not found: <path>", exit 1

Exit code is always `0` on success regardless of whether any matching tests were found. An empty result still produces a valid (empty) markdown document.

## Stdin reading

When positional args are empty, the command reads from stdin. If stdin is empty or only whitespace, the command errors with "No spec names provided". Each non-empty line is treated as one spec identifier; whitespace around each line is trimmed.

## Why this is a separate command

The markdown generation is expensive (scanning test directories, reading files, building a large document) and only makes sense when you already know which specs you care about. Splitting it from `find-changed-tests` means:

- You can run `find-changed-tests` fast for the normal "which tests changed" use case
- You can generate the AI review document only when you actually want it
- The pipeline composes: find-changed-tests → specs list → spec-reviewer
