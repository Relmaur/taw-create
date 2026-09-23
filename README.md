# TAW Create

One command to start a TAW theme. `composer create-project` asks which TAW theme you want and
installs it in the folder you named:

```bash
cd wp-content/themes

composer create-project taw/create my-theme \
  --repository='{"type":"vcs","url":"https://github.com/Relmaur/taw-create"}'
```

```
Which TAW theme do you want to start from?
  [0] taw-theme (classic: PHP blocks, Vite, Tailwind, Alpine)
  [1] taw-gutenberg (block theme, full site editing)
```

| Choice | Package | What you get |
|---|---|---|
| `theme` (default) | [`taw/theme`](https://github.com/Relmaur/taw-theme) | The classic TAW theme, on taw/core's full toolkit. Runs `npm install` for you. |
| `gutenberg` | [`taw/gutenberg`](https://github.com/Relmaur/taw-gutenberg) | The TAW block theme (full site editing), on taw/core's data layer. |

## Without the prompt (scripts, CI)

Composer doesn't pass extra arguments to an installer, so use environment variables:

```bash
TAW_STARTER=gutenberg composer create-project taw/create my-theme --no-interaction \
  --repository='{"type":"vcs","url":"https://github.com/Relmaur/taw-create"}'
```

| Variable | Values | Default |
|---|---|---|
| `TAW_STARTER` | `theme`, `gutenberg` | Prompt if interactive, otherwise `theme` |
| `TAW_STARTER_VERSION` | Any Composer constraint (`^1.12`, `0.1.0`, `dev-main`) | Latest stable release |

`--no-interaction` and `--no-dev` carry over to the theme install.

## How it works

1. Composer installs this small package into `my-theme/`. It has no dependencies, so this is
   instant.
2. Its `post-create-project-cmd` runs `composer create-project <theme>` into a hidden sibling
   folder, `.taw-create-<random>`. WordPress ignores folders that start with a dot.
3. It deletes the installer's own files from `my-theme/`, moves the theme in, and removes the
   hidden folder.

**Safety:**
- If `my-theme/` contains anything the installer didn't put there, it stops and changes nothing.
- If the theme install fails, `my-theme/` is left as it was.
- If a move fails halfway, the error names the hidden folder that still holds the complete theme.
- Commands run as argument lists, never through a shell, and environment values are validated.

## Installing a theme directly

The theme packages still work on their own:

```bash
composer create-project taw/theme my-theme --repository='{"type":"vcs","url":"https://github.com/Relmaur/taw-theme"}'
composer create-project taw/gutenberg my-theme --repository='{"type":"vcs","url":"https://github.com/Relmaur/taw-gutenberg"}'
```

## Development

```bash
composer install
composer run tools     # phpunit + phpstan, kept in tools/ so installs stay dependency-free
composer run test
composer run phpstan   # level max
```

Decisions: [`docs/adr/`](docs/adr/).
