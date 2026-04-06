# PHPUnit test method finder

The `PhpUnitTestMethodFinder` parses a PHP file using [nikic/php-parser](https://github.com/nikic/PHP-Parser) and extracts all test methods as `TestMethod` objects (see `002-test-method.md`). It implements the generic `TestMethodFinder` interface, so other test frameworks could be plugged in by providing alternative implementations.

## Test method identification

A method is a test method when it carries the `#[PHPUnit\Framework\Attributes\Test]` attribute. The legacy `test` prefix convention (without the attribute) is **not** recognised — this is a deliberate choice to keep the parser simple and the rule unambiguous.

Methods without the attribute are ignored, even if their name starts with `test`.

## Extracted metadata

For each test method, the parser records:

- `fullyQualifiedClassName` — including namespace
- `methodName`
- `startLine` / `endLine` — see `002-test-method.md` for the start-line rule (docblock or first attribute wins)
- `filePath`
- `dependentRanges` — one `LineRange` per `#[DataProvider]` method referenced, pointing at the provider's own line span
- `ticketIds` — merged from class-level and method-level `#[Ticket]` attributes

## Data providers

When a test method has `#[DataProvider('foo')]`, the parser looks up the method `foo` in the same class and records its line range as a dependent range. This means a change to the provider flags every test that uses it (see `013-changed-test-method-finder.md`).

Multiple `#[DataProvider]` attributes produce multiple dependent ranges. Tests sharing a provider each get their own copy of the range.

## Tickets

`#[Ticket('auth/login')]` can appear at class level or method level. The parser merges both:

- Class-level tickets are collected once per class
- Method-level tickets are collected per method
- The final `ticketIds` on a `TestMethod` is the union of both, preserving duplicates

If a class has `#[Ticket('auth/login')]` and a method has `#[Ticket('auth/session')]`, the resulting test gets `['auth/login', 'auth/session']`.

## Errors

If the file cannot be read (missing, unreadable), `ParseException` is thrown. If a file has no test methods, an empty list is returned — not an error.

## Non-test code

Classes without any test methods and methods without the `#[Test]` attribute are silently skipped. A single file can contain multiple classes, each with their own test methods.
