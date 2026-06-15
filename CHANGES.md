# Changelog — report_unlocker

All notable changes to this plugin are documented here.

---

## [v1.0.2] — 2026-06-15

### Changed
- AI resolution now follows the shared PlayerGames ecosystem ladder: when
  `local_playergames` is installed, every call is delegated to its hub (which
  resolves personal → site → `core_ai` internally); the direct `core_ai` path is
  used only when the hub is absent. Previously `core_ai` was tried first, which
  could override a key the user configured in the hub. Behaviour without the hub
  is unchanged.

---

## [v1.0.1] — 2026-06-09

- Add missing `COPYING.txt` (GPL v3 full license text) required by the Moodle Plugin Directory.
- Add `moodle-release.yml` GitHub Actions workflow for automated publishing to the Plugin Directory.

---

## [v1.0.0] — 2026-06-08

Initial public release.

### Features

- **Unified dashboard** — view all access restrictions (availability conditions) across activities, resources, and sections in a single, filterable page.
- **Inline editing** — edit every restriction type directly in the report without navigating to individual activity settings:
  - **Date** — date/time picker for "allow from" and "restrict until" conditions.
  - **Group** — select a specific Moodle group or "any group".
  - **Grouping** — select a grouping.
  - **Grade** — grade item selector with optional minimum % and maximum % inputs.
  - **Completion** — activity selector and expected completion state (incomplete / complete / pass / fail).
  - **User profile** — field selector (standard + custom profile fields), operator, and value input.
  - **PlayerHUD** — subtype-aware editing: minimum level, item + quantity + operator, or RPG class selector (requires `availability_playerhud` and `block_playerhud`).
  - **Nested restriction groups** — displayed read-only with a direct link to edit natively in the activity/section settings.
- **Remove checkbox** per condition — mark any restriction for deletion and save all at once.
- **Operator selector** per activity/section (`&` / `|` / `!&` / `!|`) — control whether the student must match all, any, not all, or not any of the listed conditions.
- **Per-condition visibility toggle** (`showc`) — controls whether a hidden condition is shown greyed-out or fully invisible to students (used with `&` and `!|` operators).
- **Global visibility toggle** (`show`) — single flag applied to the whole restriction group (used with `|` and `!&` operators).
- **AI Restriction Assistant** — describe changes in natural language; the assistant proposes a preview of affected conditions, and changes are applied only after explicit confirmation. Integrates with the Moodle `core_ai` subsystem (Moodle 4.5+) and falls back to `local_playergames` AI provider when available. The AI button is hidden when no provider is configured.
- **Remove all visible** — marks all restrictions matching the current filters for removal and saves after a confirmation dialog.
- **Advanced filter bar** — filter by section, by restriction type, and by activity name (with debounced live search); filters can be combined. A "Clear filters" button resets all at once.
- **Navigation integration** — a link to the report is injected automatically into the course navigation menu (visible only to users with `report/unlocker:view`).
- **Two capabilities**: `report/unlocker:view` (teachers, managers) and `report/unlocker:editconditions` (editing teachers, managers).
- **Privacy API** — null provider declared; the plugin stores no personal data.
- **Full English and Brazilian Portuguese** language support.
- **Comprehensive test suite** — 70 PHPUnit tests (parse, apply, save, integration, privacy) and 29 Behat scenarios (access, display, filters, remove-all, edit).
