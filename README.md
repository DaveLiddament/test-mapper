# TSM - Test Spec Mapper

A CLI tool that verifies your test changes match your spec changes. It diffs your current branch against a base branch (default `main`), finds which spec files and test methods have changed, and cross-references them using PHPUnit's `#[Ticket]` attribute to ensure full coverage.

## How it works

1. **Diff** -- Runs `git diff <branch> --unified=0` to get the changed line ranges across all files in the repository compared to the base branch (default `main`). By default this picks up committed changes, staged changes, and unstaged modifications to tracked files. Untracked files (never `git add`ed) are not included unless you pass `--include-untracked`.

2. **Find changed tests** -- For every changed `.php` file, parses it with [nikic/php-parser](https://github.com/nikic/php-parser) to locate all test methods (those with the `#[Test]` attribute). A test method counts as "changed" if any of its changed lines overlap with the method body, its doc comment, its attributes, or any `#[DataProvider]` method it references.

3. **Extract ticket IDs** -- Each changed test's `#[Ticket]` attributes are read. These values are the link between a test and its spec (e.g. `#[Ticket('auth/login')]`). A test can have multiple `#[Ticket]` attributes or none.

4. **Find changed specs** -- Runs `git diff --name-status <branch> -- <specs-dir>` to list spec files that have been added, modified, deleted, renamed, or copied. The specs directory prefix and file extension are stripped so that `specs/auth/login.md` becomes `auth/login`, matching the ticket ID format.

5. **Classify** -- Cross-references the ticket IDs from changed tests against the changed spec file paths, and assigns each test and spec to one of four categories (see below).

## Classifications

| Status | Meaning |
|---|---|
| **OK** | Test has `#[Ticket]` IDs and at least one matches a changed spec. |
| **No Tickets** | Test changed but has no `#[Ticket]` attribute at all. |
| **Unexpected Change** | Test has `#[Ticket]` IDs, but none match any changed spec. |
| **No Test** | A spec changed but no test references it via `#[Ticket]`. |

### Example scenario

Suppose `specs/auth/login.md` is the only changed spec, and these three test methods have changed:

```php
#[Test]
#[Ticket('auth/login')]
public function it_requires_valid_credentials(): void { /* ... */ }

#[Test]
#[Ticket('auth/login')]
#[Ticket('auth/session')]
public function it_creates_a_session_on_login(): void { /* ... */ }

#[Test]
public function it_returns_a_json_response(): void { /* ... */ }
```

Running `./vendor/bin/test-mapper --specs-dir specs` produces:

| Test | Tickets | Specs | Status |
|---|---|---|---|
| `AuthTest::it_requires_valid_credentials` | auth/login | auth/login | OK |
| `AuthTest::it_creates_a_session_on_login` | auth/login, auth/session | auth/login | OK |
| `AuthTest::it_returns_a_json_response` | | | No Tickets |

The second test has two tickets but only one matches a changed spec -- still **OK**. The third test has no `#[Ticket]` attribute, so it is flagged as **No Tickets**.

If a test referenced `#[Ticket('payments/checkout')]` but no spec at `specs/payments/checkout` had changed, it would be classified as **Unexpected Change** -- why is this test changing when that spec hasn't?

If `specs/auth/session.md` had also changed but no test referenced `auth/session`, it would appear as **No Test** -- this spec changed but nothing covers it.

## Exit codes

When `--specs-dir` is provided, the exit code is a bitmask:

| Bit | Value | Meaning |
|---|---|---|
| 0 | 1 | Tests with no tickets |
| 1 | 2 | Unexpected test changes |
| 2 | 4 | Specs with no test |

Exit code `0` means everything is properly mapped. Codes combine (e.g. `7` = all three problems). This makes it straightforward to use in CI pipelines.

Without `--specs-dir`, classification is skipped and the exit code is always `0`.

## Installation

Requires PHP 8.5+ and Git.

```bash
composer require dave-liddament/test-mapper
```

## Usage

```bash
# List changed tests (no spec validation)
./vendor/bin/test-mapper

# Validate tests against specs
./vendor/bin/test-mapper --specs-dir specs

# Include untracked files (new files not yet git added)
./vendor/bin/test-mapper --specs-dir specs --include-untracked

# Compare against a different branch
./vendor/bin/test-mapper --branch develop --specs-dir specs

# JSON output
./vendor/bin/test-mapper --specs-dir specs --format json

# GitHub Actions annotations (for CI)
./vendor/bin/test-mapper --specs-dir specs --format github
```

### Options

| Option | Short | Default | Description |
|---|---|---|---|
| `--branch` | `-b` | `main` | Base branch to diff against |
| `--specs-dir` | `-d` | _(none)_ | Specs directory (enables classification) |
| `--format` | `-f` | `table` | Output format: `table`, `json`, `specs`, or `github` |
| `--include-untracked` | `-u` | _(off)_ | Also scan untracked files (not yet `git add`ed) |
| `--test-dir` | `-t` | `tests` | Test directory to scan (repeatable, overrides config) |
| `--exclude-test-dir` | `-e` | _(none)_ | Test directory to exclude (repeatable, overrides config) |
| `--config` | `-c` | _(auto)_ | Path to config file (default: `.test-mapper.php`) |

## Spec Reviewer

The `spec-reviewer` script generates a self-contained markdown document for AI-assisted review. Given a list of spec names, it scans all PHP files to find tests with matching `#[Ticket]` attributes, then outputs the spec contents and test source code.

```bash
# Standalone -- review specific specs
./vendor/bin/spec-reviewer --specs-dir specs auth/login auth/session

# Pipeline -- review specs that passed classification
./vendor/bin/test-mapper --specs-dir specs --format specs | ./vendor/bin/spec-reviewer --specs-dir specs

# Omit spec contents (when specs aren't markdown)
./vendor/bin/spec-reviewer --specs-dir specs --no-specs auth/login
```

The output includes a table of contents with links, the full contents of each spec file, and the source code of every matching test (including data providers).

### Options

| Option | Short | Default | Description |
|---|---|---|---|
| `--specs-dir` | `-d` | _(required)_ | Specs directory |
| `--no-specs` | | _(off)_ | Omit the Specs section from output |
| `--test-dir` | `-t` | `tests` | Test directory to scan (repeatable, overrides config) |
| `--exclude-test-dir` | `-e` | _(none)_ | Test directory to exclude (repeatable, overrides config) |
| `--config` | `-c` | _(auto)_ | Path to config file (default: `.test-mapper.php`) |

Spec names are passed as positional arguments. If none are given, they are read from stdin (one per line), enabling piping from `--format specs`.

## Configuration

Both `test-mapper` and `spec-reviewer` look for a `.test-mapper.php` config file in the project root. Command-line options override config values.

```php
<?php
// .test-mapper.php

use DaveLiddament\TestMapper\Config\TestMapperConfig;

return TestMapperConfig::create()
    ->specsDir('specs')
    ->branch('develop')
    ->includeUntracked()
    ->testDirectories('tests', 'integration-tests')
    ->excludeTestDirectories('tests/Fixtures')
    ->noSpecs()
    ->build();
```

| Method | Default | Used by | Description |
|---|---|---|---|
| `specsDir(string)` | _(none)_ | both | Specs directory |
| `branch(string)` | `main` | test-mapper | Base branch to diff against |
| `includeUntracked()` | off | test-mapper | Include untracked files |
| `testDirectories(string ...)` | `tests` | both | Directories to scan for test files |
| `excludeTestDirectories(string ...)` | _(none)_ | both | Directories to exclude from scanning |
| `noSpecs()` | off | spec-reviewer | Omit Specs section from output |

If the default config file is absent, built-in defaults are used. Use `--config path/to/config.php` to specify a custom location (errors if the file doesn't exist).

## Documentation

- [How It Works](docs/HowItWorks.md) -- Detailed explanation of the analysis pipeline
- [Contributing](docs/Contributing.md) -- Development setup, running tests, and CI checks

## License

Proprietary.
