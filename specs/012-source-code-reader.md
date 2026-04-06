# Source code reader

The `SourceCodeReader` interface abstracts file reading for the `spec-reviewer` command (see `024-spec-reviewer-command.md`). The default implementation is `FileSourceCodeReader`, which reads from the filesystem.

## Interface

```
SourceCodeReader::readLines(string $filePath, int $startLine, int $endLine): string
SourceCodeReader::readFile(string $filePath): string
```

- `readLines()` — returns just the given line range from a file (inclusive, 1-based)
- `readFile()` — returns the entire file contents

Both methods trim trailing whitespace from their output so the markdown review document (see `022-ai-review-markdown-format.md`) has consistent formatting regardless of how files end.

## Use in `spec-reviewer`

When building the AI review markdown, spec-reviewer calls:

- `readFile()` for each spec file, to embed the full spec text under its heading
- `readLines()` for each test method, using the `startLine` / `endLine` recorded on the `TestMethod` (see `002-test-method.md`). Dependent ranges are read the same way so data providers appear alongside the test body

## Missing files

If a file does not exist or cannot be read, both methods return an empty string rather than throwing. This is a deliberate soft-failure: the spec-reviewer output is best-effort, and a missing file should produce an empty section rather than crash the whole run.

## Why an interface

Making this an interface lets tests inject a stub reader for unit testing the markdown formatter without touching the filesystem. The real `FileSourceCodeReader` is a thin wrapper over `file_get_contents()` and `file()`; all the interesting logic lives in the caller.
