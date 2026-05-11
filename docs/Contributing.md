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

## Parallel Development with Worktrees

You can work on multiple branches in parallel using git worktrees. Each worktree gets its own isolated Docker stack (containers, network, `vendor/`) automatically derived from the directory name. The composer download cache is shared across all worktrees, so first-install downloads only happen once.

Create a new worktree:

```bash
make worktree-new name=feat-x
cd ../test-mapper-feat-x
```

This creates a new branch `feat-x`, places the worktree at `../test-mapper-feat-x`, brings up its Docker stack, and runs `composer install`. Work in it like the main checkout — `make app/test`, `make app/ci`, etc.

Tear down when done (run from any `test-mapper` checkout, not from inside the worktree being removed):

```bash
make worktree-rm name=feat-x
```

The branch is preserved after removal so you can still merge or open a PR; use `git branch -d feat-x` to delete it once merged.

Always create worktrees as **siblings** of `test-mapper`, never inside it (a nested worktree would land inside the Docker bind mount).

## Other Make Targets

| Command | Description |
|---|---|
| `make up` | Start the Docker container |
| `make start` | Full rebuild and start (use after Dockerfile changes) |
| `make down` | Stop the container |
| `make logs` | Show live container logs |
| `make app/shell` | Open a bash shell inside the container |
| `make app/composer c="require some/package"` | Run arbitrary Composer commands |
| `make worktree-new name=feat-x` | Create a new branch + git worktree, start its stack, install deps |
| `make worktree-rm name=feat-x` | Stop the worktree's stack and remove the worktree |

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
