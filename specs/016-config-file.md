# Config file

Both `find-changed-tests` and `spec-reviewer` look for a `.test-mapper.php` config file in the project root. The file is a PHP script that returns a `TestMapperConfig` built via its fluent interface.

## Example

```php
<?php

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

## Fluent builder

`TestMapperConfig::create()` returns a fresh config. The following methods can be chained:

| Method | Default | Used by | Description |
|---|---|---|---|
| `specsDir(string)` | _(none)_ | both | Specs directory |
| `branch(string)` | `main` | find-changed-tests | Base branch to diff against |
| `includeUntracked()` | off | find-changed-tests | Include untracked files in the diff |
| `testDirectories(string ...)` | `['tests']` | both | Directories to scan for tests |
| `excludeTestDirectories(string ...)` | `[]` | both | Directories to exclude |
| `noSpecs()` | off | spec-reviewer | Omit the Specs section from the markdown output |

`build()` returns the same `TestMapperConfig` instance (it's a no-op, kept for the fluent API feel). The config is then read by the command via getter methods.

## Loading rules

The `ConfigLoader` handles loading:

- **No `--config` option and no `.test-mapper.php`** → return defaults silently
- **No `--config` option but `.test-mapper.php` exists** → load it
- **`--config path/to/config.php` provided and the file exists** → load it
- **`--config path/to/config.php` provided but the file does not exist** → `RuntimeException` with "Config file not found"
- **Loaded file does not return a `TestMapperConfig` instance** → `RuntimeException` with "must return an instance of"

The difference between "missing default config" (silent) and "missing explicit config" (error) is deliberate: defaults should work for zero-config users, but if you explicitly asked for a file and it's missing, that's a bug.

## CLI override precedence

CLI options take priority over config values. See `017-cli-option-precedence.md` for the full rules.
