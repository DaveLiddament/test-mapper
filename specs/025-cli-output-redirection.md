# CLI output redirection

Both `find-changed-tests` and `spec-reviewer` support `--output` / `-o` to write their formatter output to a file instead of stdout. This section documents the shared behaviour.

## Shared option

```
-o, --output=<path>   Write output to file instead of stdout
```

When `--output` is provided, the command opens the given path for writing, wraps the file handle in a Symfony `StreamOutput`, and passes that to the formatter in place of the default console output. The formatter is unaware of the redirection — it just writes to the `OutputInterface` it's given.

## File handling

- The file is opened with `fopen($path, 'w')`, which creates or truncates
- If the file cannot be opened (e.g. unwritable directory), the command falls back to writing to the console output — this is a defensive soft-failure rather than an error
- No newline or header is added — the output is exactly what the formatter produces

## Stdout remains quiet

When `--output` is used, nothing is written to stdout. In particular:

- `find-changed-tests --format json --output result.json` → stdout is empty, JSON is in `result.json`
- `spec-reviewer --output review.md auth/login` → stdout is empty, markdown is in `review.md`

This matters for CI jobs that capture stdout separately from artifact uploads: the `--output` path is where the content lives, stdout is free for other use.

## Use cases

- **CI artifacts** — write the markdown review to a file and upload it as a build artifact
- **Large outputs** — the AI review markdown can be several thousand lines; piping via stdout is fine but writing directly is cleaner
- **Separation of concerns** — keep a clean terminal while still generating output for processing
