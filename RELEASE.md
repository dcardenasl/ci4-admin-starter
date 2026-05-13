# Release procedure — ci4-admin-starter

This document describes how to publish a new release of `ci4-admin-starter`. The repo is versioned **only by git tags** (`composer.json` does not carry a `version` field — it is `type: project`). A tag push on `main` triggers `.github/workflows/release.yml`, which extracts the matching `## [VERSION]` block from `CHANGELOG.md` and creates the corresponding GitHub Release.

## Pre-flight checklist

Before tagging, every item below must be true. Treat any "no" as a blocker.

1. **`dev` is green on CI.** `.github/workflows/ci.yml` is passing on the latest `dev` commit (matrix PHP 8.2 / 8.3, PHPStan, CS-Fixer, `composer audit`, JS lint, i18n parity, PHPUnit, coverage soft-gate).
2. **Working tree is clean.** `git status --porcelain` returns nothing on `dev`.
3. **Local quality gate passes.**
   ```bash
   composer quality                # PHPStan + CS-Fixer (dry-run) + i18n-check
   vendor/bin/phpunit
   npm ci && npm run build:all      # Tailwind + vendored Alpine/Lucide build cleanly
   ```
4. **`CHANGELOG.md` has a dated `## [X.Y.Z]` section** at the top (under `## [Unreleased]`, which should be empty). The version string in the heading must match the tag you will push (without the `v` prefix — `2.0.0`, not `v2.0.0`).
5. **`swagger_contract.json` is current.** If the matching API release has shipped, run `composer sync-swagger` (copies from `../ci4-api-starter/public/swagger.json`) and commit the result alongside the release marker — otherwise the admin's contract reference drifts from the API.
6. **Fresh-clone smoke** (Docker path):
   ```bash
   cd /tmp && rm -rf admin-smoke && git clone --depth 1 -b dev <repo> admin-smoke && cd admin-smoke
   bash install.sh
   ```
   Verifies the install script, env stamping, Composer/npm install, and CSS build.

For a major release (`X.0.0`), also confirm:

- The `### ⚠️ Breaking Changes` and `### Migration Guide` blocks in the `[X.0.0]` section accurately describe the upgrade path from the previous minor.
- The matching `ci4-api-starter` major has shipped (or is being released the same day in lockstep) — the admin's IAM contract assumes a specific API contract.
- The `[X.0.0]: …compare/vX-1.Y.Z...vX.0.0` link at the bottom of `CHANGELOG.md` resolves on GitHub.

## Release steps

The branching model is `dev → main → tag`. Tags are always cut from `main`.

1. **On `dev`, land the release-marker commit.** This commit only finalises `CHANGELOG.md` (rename `[Unreleased]` → `[X.Y.Z] — YYYY-MM-DD`, add a fresh empty `[Unreleased]` on top) and refreshes `swagger_contract.json` if applicable. No code changes in this commit.
   ```bash
   git checkout dev
   git pull --ff-only
   # Edit CHANGELOG.md + (optional) composer sync-swagger
   git add CHANGELOG.md swagger_contract.json
   git commit -m "chore: release vX.Y.Z"
   git push origin dev
   ```
2. **Merge `dev` into `main`.** Open a PR and merge fast-forward (or via a merge commit, depending on repo policy). Do not squash — the release marker commit should survive.
   ```bash
   # Via the GitHub UI (preferred) or:
   git checkout main && git pull --ff-only
   git merge --ff-only dev
   git push origin main
   ```
3. **Tag and push.** The tag must be created **from `main`**, not from `dev`.
   ```bash
   git checkout main
   git tag vX.Y.Z
   git push origin vX.Y.Z
   ```
4. **Watch the workflow.** `.github/workflows/release.yml` will:
   - Check out the tag.
   - Run an inline `awk` over `CHANGELOG.md` to extract the body between `## [X.Y.Z]` and the next `## [` heading.
   - Create the GitHub Release with that body as the release notes. If the release already exists (re-tag scenario), it edits the existing one instead of failing.
5. **Verify the release page.** Open `https://github.com/dcardenasl/ci4-admin-starter/releases/tag/vX.Y.Z` and confirm the notes match the `[X.Y.Z]` block of `CHANGELOG.md`. An empty body almost always means a heading mismatch (stray whitespace, wrong casing).

## Post-release

- Confirm `[Unreleased]` exists on `dev` and is empty so the next cycle has a clean target.
- If `ci4-kickstart` or generated downstream admin projects need to bump their template snapshot, open the matching PRs.
- Update `TASKS.md` (workspace-level and per-repo) to close any items the release shipped.

## Rollback

A tag push triggers the release workflow exactly once. If the release notes are wrong, **prefer editing the GitHub Release directly** (the workflow is idempotent on re-tag and will overwrite the notes from `CHANGELOG.md`).

A bad tag can be retracted with:
```bash
git tag -d vX.Y.Z
git push --delete origin vX.Y.Z
```
This is only safe if **no downstream has pulled the tag yet**. Once a tag is consumed by another repo's CI or by a contributor's clone, retraction can leave inconsistent state — prefer a follow-up `vX.Y.(Z+1)` patch release with a corrective `CHANGELOG.md` entry.

## Notes specific to this repo

- **No database migrations.** The admin starter has no schema of its own; releases never need a migration step.
- **No `php spark swagger:generate`.** The admin consumes the API's swagger; `composer sync-swagger` copies the file from `../ci4-api-starter/public/swagger.json`. Run it before the release commit if the API has shipped a contract change in the same cycle.
- **Asset build is mandatory before deploy.** The runtime no longer falls back to CDN in production. Any deployment pipeline that consumes the tagged commit must run `npm ci && npm run build:all` (or `build:css` + `build:vendor`) before publishing — the Dockerfile bakes this into its multi-stage build, so Docker-based deploys get it for free.
- **API contract coupling.** A major bump (`X.0.0`) of this starter implies an API contract realignment. Coordinate with the matching `ci4-api-starter` release — pinning the admin to a major version that targets an unreleased API contract will cause every authenticated request to fail.
- **Coverage gate.** Currently a soft-fail in CI on the PHP 8.2 lane. Releases are not blocked by coverage drops, but the line-coverage % printed by `coverage:check` should not regress materially between minor versions.
