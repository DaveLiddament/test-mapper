# AI review markdown format

The `spec-reviewer` command (see `024-spec-reviewer-command.md`) generates a single self-contained markdown document that bundles each changed spec's contents with the full source code of every test referencing it. The output is designed to be fed to an AI reviewer to evaluate whether tests cover their specs.

## Document structure

```
# Changes to Review

## Contents

### Specs
- [auth/login](#authlogin) ([view file](specs/auth/login.md))
- [auth/session](#authsession) ([view file](specs/auth/session.md))

### Tests
- [App\Tests\AuthTest::itValidatesCredentials](#apptestsauthtestitvalidatescredentials) ([view file](tests/AuthTest.php))
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

#### App\Tests\AuthTest::itValidatesCredentials

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
- **Tests** — H2 grouped by spec (one H3 per spec); under each spec, one H4 per matching test (the fully qualified `Class::method` name) followed by the test's code block. The H4 anchor matches the link target used by the Contents TOC.

## Test paths

File paths shown in the Contents TOC and before each code block are rendered relative to the current working directory when possible (the `getcwd()` prefix is stripped). Paths outside the working directory are left absolute. Keeping paths relative makes the output portable — the document is meaningful when shared between machines.

## Test code blocks

Each code block contains:

1. An H4 heading with the fully qualified test name (e.g. `#### App\Tests\AuthTest::itValidatesCredentials`) — this is what the Contents TOC anchors point to
2. The file path on its own line (backticked)
3. A `php` fenced code block with the test method source
4. If the test has dependent ranges (see `013-changed-test-method-finder.md`), the data provider source is included in the same code block, sorted by start line

This ensures a reviewer sees the data provider alongside the test without hunting for it.

## `--no-specs` flag

When `--no-specs` is passed (or set in config), the `## Specs` section is omitted entirely and the Contents TOC drops its "### Specs" subsection. This is useful when specs are stored in a format the tool can't read (Confluence, Notion, tickets in an external tracker) — the test code is still bundled, just without the spec text.

## Sorting

Specs and tests are sorted alphabetically throughout. This keeps the output deterministic across runs, which matters when feeding the document through an AI: the same input produces the same output, so diffs stay meaningful.

## Source code reader

The document is assembled by reading spec files with `SourceCodeReader::readFile()` and test methods with `SourceCodeReader::readLines()`. See `012-source-code-reader.md`.
