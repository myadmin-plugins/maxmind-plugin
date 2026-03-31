# MyAdmin MaxMind Plugin

MaxMind minFraud integration plugin for MyAdmin. Provides fraud detection, risk scoring, and chargeback reporting via the MaxMind minFraud API.

- **Package type:** `myadmin-plugin`
- **Namespace:** `Detain\MyAdminMaxMind\` → `src/` (PSR-4)
- **Test namespace:** `Detain\MyAdminMaxMind\Tests\` → `tests/`
- **License:** LGPL-2.1-only

## Commands

```bash
composer install                        # install dependencies
vendor/bin/phpunit                       # run all tests (config: phpunit.xml.dist)
vendor/bin/phpunit tests/PluginTest.php  # run single test file
vendor/bin/phpunit --coverage-text       # tests with coverage report
```

```bash
composer dump-autoload                  # regenerate autoloader after PSR-4 changes
composer exec phpunit -- --filter PluginClassStructureTest  # run a specific test class
```

```bash
php bin/test_maxmind.php                # manual minFraud API test
php bin/setup_maxmind.php               # generate maxmind_output SQL table and JSON schema
```

## Architecture

**Plugin entry:** `src/Plugin.php` — `Detain\MyAdminMaxMind\Plugin` class with static `getHooks()` returning event listeners for `system.settings`, `function.requirements`, `ui.menu`

**Core fraud logic:** `src/maxmind.inc.php` — procedural functions:
- `update_maxmind($custid, $ip, $ccIdx)` — main fraud check flow using `CreditCardFraudDetection` class
- `update_maxmind_noaccount($data)` — fraud check without existing account
- `maxmind_decode($encoded)` → calls `myadmin_unstringify()`
- `get_maxmind_field_descriptions()` — returns field metadata array

**Admin UI pages:**
- `src/view_maxmind.php` — `view_maxmind()` renders fraud report for a customer via `billing/view_maxmind.tpl` Smarty template
- `src/maxmind_compare.php` — `maxmind_compare()` compares `score` vs `riskScore` across accounts using tablesorter
- `src/maxmind_lookup.php` — `maxmind_lookup()` uses modern `MaxMind\MinFraud` SDK (`maxmind/minfraud` package)

**Data files:** `src/female_names.inc.php` — global `$female_names` array for name-based risk penalty

**CLI scripts in `bin/`:**
- `bin/test_maxmind.php` — manual minFraud API test
- `bin/setup_maxmind.php` — generates `maxmind_output` SQL table and `maxmind_output_fields.json`
- `bin/convert_maxmind_serialize_to_json.php` — migrates serialized maxmind data to JSON in `accounts_ext`
- `bin/fix_maxmind_uglyness.php` — cleans HTML from `history_log` maxmind entries
- `bin/update_maxmind_log.php` — populates `maxmind_output` table from `accounts_ext`
- `bin/get_maxmind_score_distribution.sh` — SQL query for score stats by account status

## Key Dependencies

- `symfony/event-dispatcher` ^5.0 — plugin hook system via `GenericEvent`
- `maxmind/minfraud` — modern minFraud SDK (used in `src/maxmind_lookup.php`)
- `minfraud/http` — legacy `CreditCardFraudDetection` class (used in `src/maxmind.inc.php`)
- `phpunit/phpunit` ^9.6 (dev)

## Testing

- Config: `phpunit.xml.dist` with bootstrap `tests/bootstrap.php`
- Bootstrap defines `myadmin_unstringify()` stub and loads `tests/helpers/maxmind_functions.php`
- Tests are in `tests/` directory: `PluginTest.php`, `PluginClassStructureTest.php`, `FemaleNamesTest.php`, `FileExistenceTest.php`, `MaxmindDecodeTest.php`, `MaxmindFieldDescriptionsTest.php`
- Test helpers in `tests/helpers/maxmind_functions.php` provide standalone `get_maxmind_field_descriptions()` and `maxmind_decode()` stubs

## Conventions

- Commit messages: lowercase, descriptive
- Database access: `get_module_db($module)` — never PDO directly
- Logging: `myadmin_log('maxmind', $level, $message, __LINE__, __FILE__)`
- ACL checks: `has_acl('view_customer')` and `$GLOBALS['tf']->ima == 'admin'` before admin pages
- Smarty templates for rendering: `new TFSmarty()` with `->assign()` and `->fetch()`
- Constants for thresholds: `MAXMIND_LICENSE_KEY`, `MAXMIND_SCORE_LOCK`, `MAXMIND_RISKSCORE_LOCK`, `MAXMIND_RISKSCORE_DISABLE_CC`, etc.
- Plugin registration via `src/Plugin.php` static methods receiving `GenericEvent` from Symfony EventDispatcher
- i18n: wrap strings in `_('string')` for gettext

## CI

- `.scrutinizer.yml` — PHP 7.0 static analysis and coverage
- `.travis.yml` — tests on PHP 5.4–7.1 (legacy)
- `.codeclimate.yml` — duplication, phpmd checks

## Before Committing

Run `caliber refresh` before creating git commits to keep docs in sync:

```bash
caliber refresh && git add CLAUDE.md .claude/ 2>/dev/null
```

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

<!-- caliber:managed:sync -->
## Context Sync

This project uses [Caliber](https://github.com/caliber-ai-org/ai-setup) to keep AI agent configs in sync across Claude Code, Cursor, Copilot, and Codex.
Configs update automatically before each commit via `caliber refresh`.
If the pre-commit hook is not set up, run `/setup-caliber` to configure everything automatically.
<!-- /caliber:managed:sync -->
