# Unified diff parser

The `GitDiffParser` reads unified diff output (the stream produced by `git diff --unified=0`) and turns it into a list of `ChangedFile` objects.

## Format

Unified diff output looks like:

```
diff --git a/src/Foo.php b/src/Foo.php
index abc..def 100644
--- a/src/Foo.php
+++ b/src/Foo.php
@@ -10,2 +10,3 @@
-old
+new1
+new2
```

The parser only cares about two lines:

- `+++ b/<path>` — the current file path (also handles `+++ /dev/null` for deleted files)
- `@@ -old,count +new,count @@` — the hunk header giving the new-side start line and count

## Behaviour

| Scenario | Result |
|---|---|
| A hunk adds lines (e.g. `@@ -10,0 +10,3 @@`) | `ChangedLineRange(10, 12)` |
| A hunk only deletes lines (e.g. `@@ -10,3 +10,0 @@`) | skipped — no range emitted |
| Implicit count (`@@ -10 +12 @@`) | treated as count of `1` |
| A file with multiple hunks | one `ChangedLineRange` per additive hunk |
| A new file (`--- /dev/null`) | treated as all-added, `ChangedLineRange(1, N)` where N is the hunk's new count |
| A deleted file (`+++ /dev/null`) | excluded entirely from results |
| A malformed hunk header | skipped; the file is still emitted but with no ranges (see `004-changed-file.md`) |

## Why skip deletion-only hunks

If a hunk only removes lines, no new code is introduced. Any test whose method body spans the old lines would also have been deleted (or the test file wouldn't be in the diff). Skipping pure deletions prevents misleading "changed test" entries that point at non-existent code.

## Consumed by `GitDiffProvider`

The parser has no knowledge of git itself — it's a pure string-to-model function. This makes it straightforward to unit test with hand-written fixture strings. The `GitDiffProvider` (see `008-git-diff-provider.md`) is the only caller.
