# Ticket convention

The entire tool hinges on a simple convention: every test is tagged with one or more `#[Ticket]` attributes whose values match the paths of spec files (relative to the specs directory, without file extension).

## Example

A spec file at `specs/auth/login.md` is referenced by tests like:

```php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;

final class AuthTest extends TestCase
{
    #[Test]
    #[Ticket('auth/login')]
    public function itValidatesCredentials(): void
    {
        // ...
    }
}
```

## Matching rules

The `TestClassifier` (see `014-test-classifier.md`) compares ticket IDs and changed spec paths by exact string equality after normalisation:

- The specs directory prefix is stripped from the spec path (so `specs/auth/login.md` → `auth/login`)
- The file extension is stripped (any extension — `.md`, `.txt`, `.feature`, etc.)
- The ticket ID is compared as-is, no modification

A test's ticket IDs must exactly match this normalised form to count as a match.

## Class-level vs method-level

`#[Ticket]` can appear on a class, a method, or both. The parser (see `011-phpunit-test-method-finder.md`) merges them:

- Class-level tickets apply to every test method in that class
- Method-level tickets add to the set for that specific method
- Duplicates are preserved in the list but only affect matching once

This lets a test file cover a high-level spec at the class level (`#[Ticket('auth')]`) and specific sub-specs per method (`#[Ticket('auth/login')]`, `#[Ticket('auth/session')]`). Or, more commonly, the class level is left alone and each method declares its own tickets.

## Multiple tickets per test

A test can declare multiple `#[Ticket]` attributes. When the classifier computes matching specs, it intersects the test's ticket list with the changed-spec set. The test is `ok` if the intersection is non-empty, even if some of its tickets don't match anything.

## Arbitrary ticket IDs

The convention doesn't restrict the format of ticket IDs. They can be paths (`auth/login`), JIRA keys (`JIRA-123`), or anything else — as long as the spec file names match. The specs directory just needs files whose paths (stripped of prefix and extension) correspond to the ticket IDs you want to track.

## Why an attribute, not a docblock

PHPUnit attributes are native PHP syntax since 8.1, parseable without special docblock handling, and formally enforceable via a PHPStan rule. The `#[Ticket]` attribute is PHPUnit's built-in attribute, not something test-mapper invented — so using it plays nicely with any tooling that already understands PHPUnit attributes.
