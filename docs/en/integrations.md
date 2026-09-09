# 🤝 Integrations

### PlayerHUD

If both **`availability_playerhud`** and **`block_playerhud`** are installed, Unlocker detects
and displays PlayerHUD-based restrictions:

* `availability_playerhud` registers the `playerhud` condition type in Moodle's availability
  system.
* `block_playerhud` stores the item and class data that the condition references.

Unlocker reads item and class names from `block_playerhud` tables to populate the edit
selectors. The integration is automatic — no additional configuration needed.

### AI Assistant

The AI assistant is available when either of the following is configured:

* **`core_ai`** (Moodle 4.5+) — configure an AI provider in *Site administration → AI → AI
  providers*.
* **`local_aihub`** — provides BYOK AI API keys (personal and site).

Once a provider is active, the AI chat button appears in the report. Describe the restrictions
you want to change in plain language; the assistant generates a preview of all modifications
and applies them only after your confirmation.
