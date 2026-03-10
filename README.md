# Test Mapper

A CLI tool that identifies which PHPUnit test methods have changed compared to a git branch.

## Why?

When working on a feature or bug fix, you make changes across your codebase and your tests evolve alongside. Before pushing your work, you want to verify that the tests you've changed (or added) are the ones you'd expect given the requirements of the task.

Test Mapper compares your current working tree against a target branch (e.g. `main`) using `git diff`, parses the diff to find changed line ranges, then analyses your PHP test files to determine exactly which test methods have been affected. It outputs the fully qualified names of those test methods, giving you a clear picture of your test-level changes at a glance.

## Installation

Requires PHP 8.5+ and Git.

```bash
composer require dave-liddament/test-mapper
```

## Usage

```bash
# Compare against the default branch (main)
./vendor/bin/test-mapper

# Compare against a specific branch
./vendor/bin/test-mapper --branch develop
./vendor/bin/test-mapper -b develop
```

### Output

Test Mapper outputs one fully qualified test method per line:

```
App\Tests\UserServiceTest::testCreateUser
App\Tests\UserServiceTest::testDeleteUser
App\Tests\OrderProcessorTest::testApplyDiscount
```

If no test methods have changed, the output is empty.

### Examples

**Checking which tests changed on your feature branch:**

```bash
# You're on feature/add-discount-codes, branched from main
./vendor/bin/test-mapper

# Output:
# App\Tests\Order\DiscountCodeTest::testApplyValidCode
# App\Tests\Order\DiscountCodeTest::testRejectExpiredCode
# App\Tests\Order\OrderTotalTest::testTotalWithDiscount
```

You can quickly review this list against your requirements to confirm you've covered the right areas.

**Comparing against a different branch:**

```bash
# Compare against the develop branch instead of main
./vendor/bin/test-mapper --branch develop
```

**Running only the changed tests:**

```bash
# Pipe the output to PHPUnit's --filter option
./vendor/bin/test-mapper | xargs -I {} phpunit --filter {}
```

## Documentation

- [How It Works](docs/HowItWorks.md) — Detailed explanation of the analysis pipeline
- [Contributing](docs/Contributing.md) — Development setup, running tests, and CI checks

## License

Proprietary.
