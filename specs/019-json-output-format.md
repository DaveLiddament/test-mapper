# JSON output format

The `json` format emits a single pretty-printed JSON document. It's the machine-readable format intended for tooling, dashboards, and scripted post-processing.

## Two shapes

Like the table formatter (see `018-table-output-format.md`), the JSON formatter has legacy and classified modes depending on whether `--specs-dir` was provided.

## Legacy shape (no specs)

```json
{
    "tests": [
        {
            "name": "App\\Tests\\FooTest::it_works",
            "ticketIds": ["JIRA-123"]
        },
        {
            "name": "App\\Tests\\BarTest::it_also_works",
            "ticketIds": []
        }
    ]
}
```

- `tests` — always present, possibly empty
- Each entry has `name` (the fully qualified test name) and `ticketIds` (empty array if none)

## Classified shape (with specs)

```json
{
    "noTest": ["auth/login"],
    "unexpectedChange": [
        {
            "test": "App\\Tests\\FooTest::bar",
            "tickets": ["JIRA-1"],
            "matchingSpecs": []
        }
    ],
    "noTickets": [
        {
            "test": "App\\Tests\\BazTest::qux",
            "tickets": [],
            "matchingSpecs": []
        }
    ],
    "ok": [
        {
            "test": "App\\Tests\\BarTest::foo",
            "tickets": ["auth/login"],
            "matchingSpecs": ["auth/login"]
        }
    ]
}
```

All four keys are always present, even when empty. `noTest` is a flat array of spec paths; the other three are arrays of objects with `test`, `tickets`, and `matchingSpecs`.

## Formatting

The output uses `JSON_PRETTY_PRINT` and `JSON_THROW_ON_ERROR`. Pretty printing makes the output human-readable enough to diff in a PR, while still being valid JSON for consumers.

## Consumers

- CI dashboards that want structured data
- Scripts that transform the result into other formats
- Debugging when the table format hides detail you need

For line-level GitHub PR annotations, use the `github` format (see `021-github-output-format.md`). For piping into `spec-reviewer`, use the `specs` format (see `020-specs-output-format.md`).
