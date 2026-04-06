# GitHub output format

The `github` format emits [GitHub Actions workflow commands](https://docs.github.com/en/actions/using-workflows/workflow-commands-for-github-actions) so classification results show up as inline annotations on pull requests.

## Annotation severity mapping

| Classification | Annotation level |
|---|---|
| `NoTickets` | `::error::` |
| `UnexpectedChange` | `::error::` |
| `NoTest` | `::warning::` |
| `ok` | _(no output)_ |

`ok` tests are skipped entirely — no green annotations needed, just silence.

## Classified output

```
::error::No Tickets: App\Tests\FooTest::bar
::error::Unexpected Change: App\Tests\BazTest::qux (tickets: JIRA-1, JIRA-2)
::warning::No Test: auth/login
```

Each category produces its own line format:

- **No Tickets** — `::error::No Tickets: <test name>`
- **Unexpected Change** — `::error::Unexpected Change: <test name> (tickets: <comma-separated ticket IDs>)`
- **No Test** — `::warning::No Test: <spec path>`

When a test has multiple tickets in `UnexpectedChange`, all of them are listed comma-separated so reviewers can see what the test was tagged with.

## Legacy output

Without `--specs-dir`, every changed test becomes a notice:

```
::notice::Changed test: App\Tests\FooTest::it_works
```

Notices are informational — they don't fail the build but do annotate the PR diff.

## Empty output

If everything is `ok` (or there are no changes), no output is produced at all. Empty stdout in a GitHub Actions step is harmless and means no annotations appear on the PR.

## Why line numbers are omitted

The native annotation format supports `file=...,line=...` but that's not included here. The `testName` is the most useful identifier for a test reviewer, and pointing at a line number would require maintaining `file` + `line` on every `ClassifiedTest`, which complicates the data model for little gain. CI reviewers can click through to the test class from the annotation's text.
