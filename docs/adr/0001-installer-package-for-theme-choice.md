# ADR-0001: A separate installer package picks the TAW theme at create-project time

## Status

Accepted (2026-09-23). Plan: umbrella `docs/plans/editing-policies.md` § Track S.

## Context

TAW has two themes: taw-theme (classic) and taw-gutenberg (block, full site editing). The owner
wants a single command to start either, such as `composer create-project` with an argument.
Composer passes no extra arguments to a project's scripts, so a "choice" argument isn't possible.
The only inputs available are the package name, environment variables and an interactive prompt.

## Decision

1. **A new package, `taw/create`** (type `project`, public repo `Relmaur/taw-create`, a submodule
   of the umbrella). Its `post-create-project-cmd` chooses the starter: `TAW_STARTER` first, then
   a prompt when interactive, then `theme` by default. `TAW_STARTER_VERSION` optionally sets a
   version constraint.
2. **It installs the theme with a nested `composer create-project`** into a hidden sibling folder,
   then swaps the files into the target. It never re-implements what Composer does.
3. **Safety rules:**
   - It refuses a target that contains anything the installer doesn't own.
   - A failed install leaves the target untouched.
   - A failed move keeps the complete theme and prints where it is.
   - Commands run as argument lists (`proc_open`), never through a shell.
   - Environment values are validated against an allow-list or pattern, and a leading `-` is
     rejected so a value can't become a Composer option.
4. **No runtime dependencies.** phpunit and phpstan live in `tools/composer.json`, and phpstan sees
   Composer's classes through `stubs/composer.php`.

## Trade-offs

- **Two downloads per install** (the installer, then the theme). The installer is a few KB, so
  that's negligible.
- **Rejected: a prompt inside taw-theme** that swaps in taw-gutenberg. The classic theme would
  have to know about and download the other theme, and its package name would lie about what you
  get.
- **Rejected: separate package names only** (`taw/theme`, `taw/gutenberg`). They still work, but
  there's no single entry point, which is what the owner asked for.
- **Rejected: Symfony Process** (the original plan). `proc_open`'s array form gives the same
  no-shell guarantee without adding a dependency to every install.

## Consequences

- Every new top-level file in this repo must be listed in `Project::OWN_ENTRIES`.
- Each theme must have a stable tag for the default install. taw-gutenberg got `v0.1.0` for this.
- The docs lead with `taw/create`. The direct commands stay documented as alternatives.
