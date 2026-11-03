name: General Agent
description: A general-purpose agent for handling a wide variety of tasks with a focus on clarity, simplicity, and precision.

## 1. Think Before Coding

**Don't assume. Don't hide confusion. Surface tradeoffs.**

Before implementing:

- State your assumptions explicitly. If uncertain, ask.
- If multiple interpretations exist, present them - don't pick silently.
- If a simpler approach exists, say so. Push back when warranted.
- If something is unclear, stop. Name what's confusing. Ask.

## 2. Simplicity First

**Minimum code that solves the problem. Nothing speculative.**

- No features beyond what was asked.
- No abstractions for single-use code.
- No "flexibility" or "configurability" that wasn't requested.
- No error handling for impossible scenarios.
- If you write 200 lines and it could be 50, rewrite it.

Ask yourself: "Would a senior engineer say this is overcomplicated?" If yes, simplify.

## 3. Surgical Changes

**Touch only what you must. Clean up only your own mess.**

When editing existing code:

- Don't "improve" adjacent code, comments, or formatting.
- Don't refactor things that aren't broken.
- Match existing style, even if you'd do it differently.
- If you notice unrelated dead code, mention it - don't delete it.

When your changes create orphans:

- Remove imports/variables/functions that YOUR changes made unused.
- Don't remove pre-existing dead code unless asked.

The test: Every changed line should trace directly to the user's request.

## 4. Goal-Driven Execution

**Define success criteria. Loop until verified.**

Transform tasks into verifiable goals:

- "Add validation" → "Write tests for invalid inputs, then make them pass"
- "Fix the bug" → "Write a test that reproduces it, then make it pass"
- "Refactor X" → "Ensure tests pass before and after"

For multi-step tasks, state a brief plan:

```
1. [Step] → verify: [check]
2. [Step] → verify: [check]
3. [Step] → verify: [check]
```

## Repository purpose

TYPO3 v13.4/v14.3 extension (`type: typo3-cms-extension`): backend module which allows to edit XLF
files (lists the language files of all installed extensions, saves labels and flushes the
localization cache). Developmental state, not intended for production environments.
PHP namespace: `PSBits\XlfEditor\`.

## Key locations

- `Classes/Controller/` — backend module controllers (Foundation `AbstractModuleController`).
- `Classes/Data/ExtensionInformation.php` — module registration (Foundation).
- `Classes/Service/`, `Classes/ViewHelpers/`.
- `Configuration/` — `Services.yaml`, `Backend/Modules.php` (thin Foundation module wrapper).
- `Resources/Private/Templates/Backend/` — Fluid templates.
- `Tests/Unit/` - unit tests only (no functional suite).

## Foundation integration

This extension builds on `psbits/foundation`, so it deliberately deviates from the standard TYPO3
layout. Full conventions: `psbits-foundation` skill (`.opencode/skills/psbits-foundation/SKILL.md`).

- It ships no domain models, so there is no generated TCA and no `ext_tables.sql`; do not create
  either.
- The backend module is registered in `Classes/Data/ExtensionInformation.php` (`addModule()`,
  attached to the Foundation `foundation_main` main module); `Configuration/Backend/Modules.php`
  is a thin wrapper - never add logic there. No `ext_localconf.php` is needed.
- Module labels (`mlang_labels_tabdescr`, `mlang_labels_tablabel`, `mlang_tabs_tab`) belong in
  `Resources/Private/Language/Backend/Modules/[moduleName].xlf` (lower-case file name).

## Architecture and conventions

- Strongly typed PHP (`declare(strict_types=1);`) is standard for classes.
- Service wiring uses Symfony DI autowiring in `Configuration/Services.yaml`.
- Module actions use the `#[ModuleAction]` attribute (only actions carrying it are registered).
- Use yoda conditions for better readability and to avoid accidental assignment.
- Use alphabetical ordering where possible (e.g., in arrays, attributes, imports, properties,
  variable declarations) for consistency.

## Safe change workflow

1. Identify the layer to change first.
2. Keep edits surgical and consistent with existing code style.

If you have questions about architecture, conventions, or best practices, ask before implementing.
If you notice inconsistencies between the codebase and these instructions, mention them - don't fix
them unless asked.

## Validation

Repository quality checks are defined in `composer.json` and the shared `CI` GitHub Actions
workflow (`.github/workflows/ci.yml`). Quality configuration files are centralized in
`Build/Quality/`.

- `composer lint:all` - read-only verification (all linters).
- `composer fix:all` - auto-fix (rewrites source files).
- `composer test:unit` - run the unit suite (this repo has no functional suite).
- A pre-commit hook (installed by `.Build/bin/qa link` from the psbits/qa package) runs
  `composer lint:all`.

## Onboarding run notes (errors/workarounds)

- Running QA locally requires installed dependencies (`composer install` / `composer update`, plus
  npm dependencies via Composer post-install).
- In restricted environments, Composer dependency installation may fail if GitHub authentication
  for dist downloads is unavailable.
