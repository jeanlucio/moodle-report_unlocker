# Changelog — report_unlocker

All notable changes to this plugin are documented here.

---

## [v0.2.0] — 2026-06-06

### Added

- All access restriction types are now visible in the report (previously only date conditions were shown).
- **Group** and **Grouping** restrictions are fully editable (select a specific group/grouping or "any group").
- **Grade, Completion, User profile, PlayerHUD** restrictions are shown as read-only with an option to remove them.
- Remove checkbox on every condition: mark any restriction for deletion and save once.
- Removing all conditions from an activity/section correctly clears the `availability` field.
- New "Filter by type" dropdown in the filter bar, combinable with section and name search.
- Strings updated to be generic ("access restrictions") instead of date-specific.

---

## [v0.1.0] — 2026-06-06

### Added

- Initial release.
- List all course activities and sections that have date-based access restrictions.
- Edit all date conditions in a single form and save everything with one click.
- Supports both "allow from" (`>=`) and "restrict until" (`<`) date conditions.
- Non-date conditions (grade, group, profile field, etc.) are preserved untouched on save.
- Separate collapsible sections for Activities/Resources and Sections.
- Navigation link injected automatically into the course Reports menu.
- Two capabilities: `report/unlocker:view` and `report/unlocker:editconditions`.
- Privacy API null provider declared.
- Full English and Brazilian Portuguese language support.
