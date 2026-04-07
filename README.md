# TSM - Test Spec Mapper

A lightweight convention and CLI for keeping PHPUnit tests linked to in-repo specs. Built for the AI-assisted code review era.

## Why TSM exists

Two problems, one solution.

### Specs outlive the tools that hold them

Requirements get scattered across Trello, Jira, Confluence, Notion, Linear, Slack threads, and stale PR descriptions. Then teams migrate, projects switch tools, instances get archived, and the *why* behind the code evaporates. Five years in, all that's left is the code itself -- and nobody remembers what behaviour it was supposed to have, or which tests are guarding which intent.

TSM's first principle: **specs belong in the repo, alongside the code they describe.** A markdown file in `specs/` lives as long as the code does. It gets versioned, diffed, code-reviewed, and survives every tooling migration the team ever makes. No external system to depend on, nothing to migrate, nothing to lose.

### AI-assisted review needs intent, not just diffs

The bottleneck for AI code review isn't "can the model read code." It's "can we hand the model the right slice of context -- including what the change is *supposed to do*." A diff on its own only answers "is the code well-formed." The interesting question is "does this change satisfy the intent it claims to satisfy."

TSM's second principle: **make the spec/test link first-class, so reviewers (human or AI) can evaluate a PR against intent.** A test method declares which spec it verifies via a single `#[Ticket('auth/login')]` attribute. From there, you can answer:

- Which tests guard the behaviour described in this spec?
- Did this PR's test changes correspond to a documented behaviour change?
- Has any spec been edited without a test acknowledging it?

The companion `spec-reviewer` command bundles a spec plus every test that cites it into a single self-contained markdown document -- exactly the context an AI reviewer (or a new hire, or yourself six months from now) needs.

### What you get

- **Specs as durable, version-controlled artifacts.** They outlive any external system.
- **A lightweight convention, not a framework.** One attribute per test, plain markdown for specs. No DSL, no BDD harness, no methodology to adopt.
- **Visibility, not enforcement.** TSM surfaces spec/test correspondence as a signal for reviewers to interpret in context. A refactor PR that triggers "Unexpected Change" isn't a failure -- it's information for the human or AI doing the review.
- **Composable with AI workflows.** `spec-reviewer` produces a self-contained markdown bundle of intent + verification, ready to feed an LLM.

## Installation

Requires PHP 8.3, 8.4, or 8.5, and Git.

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
| `--output` | `-o` | _(stdout)_ | Write output to file instead of stdout |

## spec-reviewer

The `spec-reviewer` command produces a self-contained markdown bundle of a spec plus every test that references it -- ideal for AI-assisted review, onboarding, and refactor planning.

```bash
# Review specific specs
./vendor/bin/spec-reviewer --specs-dir specs auth/login auth/session

# Pipe from test-mapper
./vendor/bin/test-mapper --specs-dir specs --format specs | ./vendor/bin/spec-reviewer --specs-dir specs
```

See [docs/SpecReviewer.md](docs/SpecReviewer.md) for full options and output format.

## Configuration

A `.test-mapper.php` file in the project root can configure both commands. CLI options override config values. See [docs/Configuration.md](docs/Configuration.md) for the full reference.

## Documentation

- [How It Works](docs/HowItWorks.md) -- pipeline mechanics, classifications, worked example, exit codes
- [Configuration](docs/Configuration.md) -- config file format and methods
- [spec-reviewer](docs/SpecReviewer.md) -- command reference and output format
- [Contributing](docs/Contributing.md) -- development setup, running tests, and CI checks

## License

MIT
