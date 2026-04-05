# How It Works

## test-mapper

### 1. Diff

Runs `git diff <branch> --unified=0` to get the raw diff between your current state and the target branch (default `main`). Parses the unified diff output to extract file paths and changed line ranges. Only committed, staged, and unstaged modifications to tracked files are included. Pass `--include-untracked` to also pick up files that have never been `git add`ed (detected via `git ls-files --others --exclude-standard`).

### 2. Find changed tests

For each changed `.php` file that falls within the configured test directories (default `tests/`), uses [nikic/php-parser](https://github.com/nikic/PHP-Parser) to build an AST and locate all PHPUnit test methods. Test methods are identified by the `#[Test]` attribute -- the legacy `test` prefix naming convention without the attribute is not detected.

A test method counts as "changed" if any changed line overlaps with:
- The method body
- Its doc comment
- Its attributes
- Any `#[DataProvider]` method it references

Files outside the configured test directories or inside excluded directories are filtered out via the `TestDirectoryFilter`.

### 3. Extract ticket IDs

Each changed test's `#[Ticket]` attributes are read. These values link tests to specs (e.g. `#[Ticket('auth/login')]`). Ticket attributes can appear at both the class level (inherited by all methods) and the method level. A test can have multiple `#[Ticket]` attributes or none.

### 4. Find changed specs (when `--specs-dir` is provided)

Runs `git diff --name-status <branch> -- <specs-dir>` to list spec files that have been added, modified, deleted, renamed, or copied. The specs directory prefix and file extension are stripped so that `specs/auth/login.md` becomes `auth/login`, matching the ticket ID format.

### 5. Classify

Cross-references the ticket IDs from changed tests against the changed spec file paths:

| Status | Meaning |
|---|---|
| **OK** | Test has `#[Ticket]` IDs and at least one matches a changed spec |
| **No Tickets** | Test changed but has no `#[Ticket]` attribute |
| **Unexpected Change** | Test has `#[Ticket]` IDs, but none match any changed spec |
| **No Test** | A spec changed but no test references it via `#[Ticket]` |

### 6. Output

Results are formatted according to `--format`:
- **table** (default) -- Symfony Console table with colour-coded status
- **json** -- structured JSON for programmatic consumption
- **specs** -- one OK spec name per line, for piping into `spec-reviewer`

## spec-reviewer

The `spec-reviewer` is a standalone script that generates a markdown document for AI-assisted review. It does not use git or diffs.

### 1. Collect spec names

Spec names are taken from positional arguments or read from stdin (one per line). This enables piping from `test-mapper --format specs`.

### 2. Scan test files

Recursively finds all `.php` files within the configured test directories (default `tests/`), excluding any configured exclude directories and `vendor/`. Each file is parsed with PHP-Parser to find test methods with `#[Test]` attributes.

### 3. Match tests to specs

For each test method found, its `#[Ticket]` attribute values are compared against the requested spec names. Tests whose ticket IDs match are grouped by spec.

### 4. Generate markdown

The output includes:
- **Table of contents** -- internal anchor links to each spec and test, plus relative file path links
- **Specs section** -- each spec as an H3 with the spec file's full markdown contents (omitted with `--no-specs`)
- **Tests section** -- grouped by spec, each test shown as a PHP code block with its file path. Data provider methods are included in the same code block, sorted by line number.

## Configuration

Both commands read a `.test-mapper.php` config file from the project root (or a custom path via `--config`). The config file returns a `TestMapperConfig` object built with a fluent interface. Command-line options override config values. See the README for the full list of config methods.

## Test directory filtering

Both commands scope their work to configured test directories. The `TestDirectoryFilter` checks each file's relative path against:

1. **Exclude directories** -- checked first. If a file's path starts with any excluded directory, it is skipped.
2. **Test directories** -- if a file's path starts with any test directory, it is included.
3. Otherwise, the file is excluded.

The default test directory is `tests/`. This can be overridden via the config file (`testDirectories(...)`) or CLI options (`--test-dir`, `--exclude-test-dir`).
