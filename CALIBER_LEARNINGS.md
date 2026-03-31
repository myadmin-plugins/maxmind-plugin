# Caliber Learnings

Accumulated patterns and anti-patterns from development sessions.
Auto-managed by [caliber](https://github.com/caliber-ai-org/ai-setup) — do not edit manually.

- **[gotcha]** When working inside a `vendor/detain/myadmin-*-plugin/` subdirectory, subagents launched via the Agent tool are sandboxed to that plugin directory and cannot access sibling plugin directories under `vendor/detain/`. To compare patterns across plugins, either work from the parent `mystage/` directory or read files directly with the Read tool (which has broader access than subagent sandboxes).
- **[pattern]** To verify file path references in config/doc files for this plugin, glob `src/*` and `tests/**/*` from the plugin root — the full file list is small enough to enumerate in one call. Check `composer.json` for package name and autoload mappings rather than guessing.
- **[gotcha]** The plugin has no `.github/copilot-instructions.md` file — the `caliber refresh` command in CLAUDE.md references it but it may not exist in every plugin repo. The `2>/dev/null` redirect handles this, but don't reference it as a valid path in docs.
