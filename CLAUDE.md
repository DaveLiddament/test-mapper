## Docker

This project uses Docker for all PHP operations. Use the Makefile targets below instead of direct Docker commands.

Xdebug is enabled by default. To disable it, set `XDEBUG_MODE=off` in `docker-compose.yml` and restart the container.

## Running Commands

This project uses a Makefile to wrap Docker commands. Always prefer `make` targets over direct Docker commands.

Run `make help` to see all available targets.

Common commands:
- `make up` — Start the Docker container (use this for day-to-day work)
- `make start` — Full rebuild and start (only needed after Dockerfile changes)
- `make app/shell` — Open a shell in the container
- `make app/ci` — Run all CI checks
- `make app/test` — Run tests (`make app/test c="--filter=MyTest"` for specific tests)
- `make app/composer c="require some/package"` — Run composer commands

Only fall back to `docker compose exec app <command>` if there is no suitable Make target.

## Parallel Worktrees

You can run multiple feature branches in parallel via git worktrees. Each worktree gets its own isolated Docker stack (containers, network, `vendor/`) derived from the directory name. The composer download cache is shared across all worktrees via a Docker volume named `test-mapper-composer-cache`, which Compose creates automatically on first `make up`.

Create a new worktree:

    make worktree-new name=feat-x
    cd ../test-mapper-feat-x

Work in it like the main checkout (`make app/test`, `make app/ci`, etc.).

Tear down when done (run from any `test-mapper` checkout, not from inside the worktree being removed):

    make worktree-rm name=feat-x

Always create worktrees as siblings of `test-mapper`, never inside it.
