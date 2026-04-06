# Table output format

The `table` format (default) renders results as a Symfony Console table. It's the human-readable format intended for developers running the tool locally.

## Two modes

The formatter has two modes that depend on whether classification ran:

- **Legacy mode** (no `--specs-dir` provided) — two columns: Test, Tickets
- **Classified mode** (`--specs-dir` provided) — four columns: Test, Tickets, Specs, Status

## Legacy mode

One row per changed test. Columns:

- **Test** — fully qualified name (`App\Tests\FooTest::bar`)
- **Tickets** — comma-separated ticket IDs from the test's `#[Ticket]` attributes, or blank

Empty inputs (no changed tests) produce no output at all — not an empty table.

## Classified mode

Rows come from the `TestClassificationResult` (see `006-classified-test.md`), grouped into the four categories. Columns:

- **Test** — test name (blank for `NoTest` rows where no test exists)
- **Tickets** — test's ticket IDs (blank for `NoTest` rows)
- **Specs** — matching spec path (for `ok` rows: the intersection; for `NoTest` rows: the orphaned spec; blank for others)
- **Status** — the category label: `OK`, `No Tickets`, `Unexpected Change`, or `No Test`

Empty classification results (no problems and no `ok` tests) produce no output.

## Colour coding

When output is a terminal that supports ANSI colour (detected by Symfony Console automatically):

- **Red background** — error rows (`UnexpectedChange`)
- **Yellow foreground** — warning rows (`NoTest`)
- **Default** — everything else

The colour applies to the whole cell content so the row stands out at a glance.

## Why this format by default

The table format is designed for a human scanning a local terminal. For CI contexts, `json` or `github` (see `019-json-output-format.md`, `021-github-output-format.md`) are more appropriate.
