# AI review markdown format

The `spec-reviewer` command (see `024-spec-reviewer-command.md`) generates a single self-contained markdown document that bundles each changed spec's contents with the full source code of every test referencing it. The output is designed to be fed to an AI reviewer to evaluate whether tests cover their specs.

## Document structure

```
# Changes to Review

## Contents

### Specs
- [auth/login](#auth-login) ([view file](specs/auth/login.md))
- [auth/session](#auth-session) ([view file](specs/auth/session.md))

### Tests
- [App\Tests\AuthTest::itValidatesCredentials](#app-tests-authtest-itvalidatescredentials) ([view file](tests/AuthTest.php))
- ...

---

## Specs

### auth/login

`specs/auth/login.md`

<full contents of the spec file>

---

### auth/session
...

---

## Tests

### auth/login

`tests/AuthTest.php`

```php
#[Test]
#[Ticket('auth/login')]
public function itValidatesCredentials(): void
{
    // ... full method body ...
}
```

### auth/session
...
```

## Sections

- **Title** — H1 "Changes to Review"
- **Contents** — H2 with two H3 subsections (Specs, Tests) listing every item as an internal anchor link and an external file link
- **Specs** — H2 with one H3 per spec; each contains the file path (backticked) and the spec's full markdown contents, separated by `---` horizontal rules
- **Tests** — H2 grouped by spec (one H3 per spec); each group contains one code block per matching test method

## Test code blocks

Each code block contains:

1. The file path on its own line (backticked)
2. A `php` fenced code block with the test method source
3. If the test has dependent ranges (see `013-changed-test-method-finder.md`), the data provider source is included in the same code block, sorted by start line

This ensures a reviewer sees the data provider alongside the test without hunting for it.

## `--no-specs` flag

When `--no-specs` is passed (or set in config), the `## Specs` section is omitted entirely and the Contents TOC drops its "### Specs" subsection. This is useful when specs are stored in a format the tool can't read (Confluence, Notion, tickets in an external tracker) — the test code is still bundled, just without the spec text.

## Sorting

Specs and tests are sorted alphabetically throughout. This keeps the output deterministic across runs, which matters when feeding the document through an AI: the same input produces the same output, so diffs stay meaningful.

## Source code reader

The document is assembled by reading spec files with `SourceCodeReader::readFile()` and test methods with `SourceCodeReader::readLines()`. See `012-source-code-reader.md`.
