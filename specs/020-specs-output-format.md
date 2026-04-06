# Specs output format

The `specs` format emits a flat, newline-separated list of OK spec names. It's a pipe-friendly format designed as input for the `spec-reviewer` command (see `024-spec-reviewer-command.md`).

## Output shape

One spec per line, alphabetically sorted, deduplicated:

```
auth/login
auth/session
```

No headers, no formatting, no leading or trailing blank lines. Each line is a canonical spec identifier (the stripped form — see `005-changed-spec-file.md`).

## Source of spec names

Spec names are collected from the `matchingSpecs` field of every `ok` classified test (see `006-classified-test.md`). Each unique spec appears once regardless of how many tests matched it.

Only specs that are successfully matched (i.e. from `ok` tests) are emitted. Specs in `noTest` or specs that were matched by tests in `unexpectedChange` or `noTickets` don't appear here — this format is specifically about specs whose tests are in good shape.

## Requires classification

The formatter only produces output when classification has run (i.e. `--specs-dir` was provided). Without classification there's no way to know which specs matched tests, so calling `--format specs` without `--specs-dir` is a no-op and produces no output.

## Use case: piping into `spec-reviewer`

The intended pipeline is:

```bash
./vendor/bin/test-mapper --specs-dir specs --format specs \
  | ./vendor/bin/spec-reviewer --specs-dir specs > review.md
```

`test-mapper` decides which specs are worth reviewing (the ones whose tests are `ok`), and `spec-reviewer` generates a markdown document containing each spec's contents plus the full source code of every matching test. The result is a single self-contained document suitable for AI-assisted code review.

See `022-ai-review-markdown-format.md` for the output of that second stage.
