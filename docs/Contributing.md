# Contributing

## Prerequisites

- Docker and Docker Compose
- Make (optional, but recommended)

All PHP tooling runs inside a Docker container, so you don't need PHP installed locally.

## Getting Started

**1. Clone the repository:**

```bash
git clone git@github.com:dave-liddament/test-mapper.git
cd test-mapper
```

**2. Build and start the Docker container:**

```bash
make start
```

For subsequent sessions, use `make up` (skips the Docker image rebuild).

**3. Install dependencies:**

```bash
make app/setup
```

This runs `composer install` and installs all dev tool binaries (PHPStan, PHP-CS-Fixer, etc.) via the [composer-bin-plugin](https://github.com/bamarni/composer-bin-plugin).

## Running Checks

### Full CI Suite

Run all checks in one go:

```bash
make app/ci
```

This runs (in order):
1. Composer validate
2. Parallel lint
3. Unused composer package check
4. Missing composer requirement check
5. Code style fix
6. PHPStan static analysis
7. PHPUnit tests

### Individual Checks

| Command | Description |
|---|---|
| `make app/test` | Run PHPUnit tests |
| `make app/test c="--filter=MyTest"` | Run a specific test |
| `make app/phpstan` | Run PHPStan (level 8) |
| `make app/cs-fix` | Fix code style automatically |
| `make app/cs` | Check code style (dry run, no changes) |
| `make app/lint` | Run parallel-lint |
| `make app/composer-unused` | Check for unused Composer packages |
| `make app/require-checker` | Check for missing Composer requirements |
| `make app/composer-validate` | Validate `composer.json` |

### Running Test Mapper Itself

```bash
make app/test-mapper
make app/test-mapper c="--branch develop"
```

## Composer Scripts

If you prefer working inside the container directly (via `make app/shell`), the same checks are available as Composer scripts:

```bash
composer test         # PHPUnit
composer phpstan      # PHPStan
composer cs-fix       # Fix code style
composer cs           # Check code style (dry run)
composer lint         # Parallel lint
composer ci-local     # Full CI suite (with auto code style fix)
composer ci           # Full CI suite (code style check only, used in CI pipelines)
```

## Other Make Targets

| Command | Description |
|---|---|
| `make up` | Start the Docker container |
| `make start` | Full rebuild and start (use after Dockerfile changes) |
| `make down` | Stop the container |
| `make logs` | Show live container logs |
| `make app/shell` | Open a bash shell inside the container |
| `make app/composer c="require some/package"` | Run arbitrary Composer commands |

Run `make help` to see all available targets.

## Project Structure

```
src/
  Command/                  # Symfony Console command
  Diff/Git/                 # Git diff execution and parsing
  Exception/                # Domain exceptions
  Model/                    # Value objects (ChangedFile, TestMethod, etc.)
  TestAnalyzer/PhpUnit/     # PHPUnit test method detection via PHP-Parser
tests/
  Fixtures/                 # Test fixture files (sample diffs and PHP classes)
  ...                       # Mirror of src/ structure
bin/
  test-mapper               # CLI entry point
```

## Code Standards

- PHPStan level 8 (strictest) with additional strict rules
- PHP-CS-Fixer with PSR-1, PSR-2, and Symfony rule sets
- Test methods must use the `#[Test]` attribute (not the `test` prefix convention)
