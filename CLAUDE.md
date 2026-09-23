# CLAUDE.md — TAW Create

Full reference: **`AGENTS.md`** in this folder. Also read the umbrella's `AGENTS.md` and
`docs/STATE.md` (one level up).

- `composer create-project taw/create <dir>` → prompt or `TAW_STARTER` → nested create-project →
  swap files into `<dir>`.
- The root `composer.json` has no dependencies. Dev tools live in `tools/` (`composer run tools`).
- A new top-level file needs a matching entry in `Project::OWN_ENTRIES`.
- `composer run test` + `composer run phpstan` (level max) before committing.
