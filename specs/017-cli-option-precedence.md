# CLI option precedence

Both commands resolve their options by combining CLI arguments, the config file (see `016-config-file.md`), and built-in defaults — in that order.

## Resolution order

For any given option, the effective value is:

1. The CLI argument if the user explicitly passed one
2. Otherwise, the config file value if one is set
3. Otherwise, the built-in default

## Value-taking options (replace on override)

These options take a value and CLI fully replaces the config entry:

| Option | Config method | Default |
|---|---|---|
| `--branch` | `branch()` | `main` |
| `--specs-dir` | `specsDir()` | _(none)_ |
| `--test-dir` | `testDirectories()` | `['tests']` |
| `--exclude-test-dir` | `excludeTestDirectories()` | `[]` |

For `--test-dir` and `--exclude-test-dir`, which can be specified multiple times, passing **any** CLI value replaces the entire config list — the two aren't merged.

## Flag options (additive)

These are boolean flags. Passing the flag always enables the behaviour, but omitting the flag leaves the config value intact:

| Option | Config method | Default |
|---|---|---|
| `--include-untracked` | `includeUntracked()` | `false` |
| `--no-specs` | `noSpecs()` | `false` |

In other words: flags can turn a feature on via CLI even if the config didn't, but they can't turn it off. If the config sets `includeUntracked()` and the user omits the flag, untracked files are still included. To disable such a flag, remove it from the config.

## `--config`

The `--config` option itself doesn't follow the normal rules — it tells the loader *which* config to load. If specified, the file must exist (errors otherwise). If omitted, `.test-mapper.php` is tried silently.

## Why this design

Value-taking overrides are the standard "CLI wins" pattern. Flag additivity is a deliberate choice: users who enable a feature in config usually do so consistently, and the ability to add a second flag to enable an already-enabled feature is harmless. Supporting a hypothetical `--no-include-untracked` would add complexity for no real use case.
