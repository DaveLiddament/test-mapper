# Line range

Two value objects represent line ranges:

- `LineRange` — a simple `startLine` / `endLine` pair, both inclusive
- `ChangedLineRange` — the same shape but semantically represents a range *changed* in a diff

Both are used heavily by the change-detection algorithm (see `013-changed-test-method-finder.md`).

## Overlap detection

A `ChangedLineRange` overlaps another line range when any line is common to both.

```
ChangedLineRange::overlapsRange(startLine, endLine): bool
```

The algorithm returns `true` if and only if:

```
$this->startLine <= $endLine && $this->endLine >= $startLine
```

## Edge cases

| Scenario | Overlap? |
|---|---|
| Ranges share a single line | yes |
| One range entirely inside the other | yes |
| Adjacent but non-overlapping (e.g. `[1,5]` and `[6,10]`) | no |
| Both ranges are the same single line | yes |
| A zero-line range | never overlaps — deletion-only hunks don't count as changes |

The "deletion-only" case is important: when the unified diff parser (see `009-unified-diff-parser.md`) encounters a hunk that only removes lines without adding any, the resulting `ChangedLineRange` has no lines and is treated as a non-change. This prevents deletions of unrelated lines from flagging unrelated tests.

## Composition into `ChangedFile`

A `ChangedFile` (see `004-changed-file.md`) holds a list of `ChangedLineRange` entries. Its `overlapsRange()` method returns `true` if any of its ranges overlap.
