# How It Works

1. Runs `git diff <branch> --unified=0` to get the raw diff between your current state and the target branch
2. Parses the diff to extract which files changed and the exact line ranges that were modified
3. For each changed `.php` file, uses [PHP-Parser](https://github.com/nikic/PHP-Parser) to build an AST and locate all PHPUnit test methods (identified by the `#[Test]` attribute)
4. Checks whether any changed line ranges overlap with a test method's line span (including its docblock and attributes)
5. Outputs the fully qualified name of each affected test method

**Note:** Test methods are identified by the `#[Test]` attribute. Methods using the legacy `test` prefix naming convention without the attribute are not detected.
