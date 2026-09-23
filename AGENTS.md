# AGENTS.md — TAW Create

The `taw/create` installer: `composer create-project taw/create <dir>` asks for a TAW theme
(taw-theme or taw-gutenberg) and installs it in `<dir>`. Part of the TAW umbrella
(`TAW/taw-create`, a submodule). Read the umbrella's AGENTS.md and `docs/STATE.md` first.

## Layout

| Path | Purpose |
|---|---|
| `src/Installer.php` | `post-create-project-cmd` handler: the thin Composer adapter, not unit-tested |
| `src/Starter.php` | The fixed list of themes (package, repository, label, next steps) |
| `src/Choice.php` | Picks the starter (`TAW_STARTER` → prompt → default) and validates `TAW_STARTER_VERSION` |
| `src/Project.php` | The nested create-project and the file swap, with the safety rules |
| `src/Prompt.php`, `src/Runner.php`, `src/ProcessRunner.php` | Composer IO prompt; the process seam that tests replace |
| `stubs/composer.php` | Composer class declarations for phpstan only |
| `tools/composer.json` | phpunit + phpstan (not in the root composer.json, see ADR-0001) |

## Commands

```bash
composer install && composer run tools
composer run test
composer run phpstan   # level max
```

## Rules

- **The root `composer.json` stays dependency-free.** Everything in it gets installed on every
  `create-project`. Dev tools go in `tools/`.
- **Adding a new top-level file to this repo means adding it to `Project::OWN_ENTRIES`.** If you
  don't, the installer refuses to run, because it sees a file it doesn't own.
- **Adding a starter** means adding an entry in `Starter::all()` plus a test, and updating the
  README table. Starters are a fixed list: never install a package name that came from input.
- Run external commands only through `Runner` with an argument list. Never use a shell string.
- A theme release that changes its own `post-create-project-cmd` should get an end-to-end run
  through this installer (README § How it works).
- Umbrella rules apply: confirm every state-changing git operation (scope and actor), bump the
  umbrella pointer after committing here, and push this repo before the umbrella.
