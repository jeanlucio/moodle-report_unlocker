# 🧪 Automated Tests

Unlocker ships with **82 PHPUnit test cases** and a **25-scenario Behat suite**, run on every
CI push across the full matrix (Moodle 4.5 → 5.x, PostgreSQL & MariaDB).

### PHPUnit — Unit & Integration Tests

| Test file | Cases | What is covered |
|-----------|------:|----------------|
| `ai/assistant_test.php` | 1 | The assistant class can be constructed against a real course's condition set without error — a smoke test; the AI request/response cycle itself is exercised through the Behat suite and manual verification, not unit-mocked here |
| `local/condition_writer_test.php` | 43 | The pure parse-and-rewrite logic behind every inline edit and bulk removal: malformed/empty/`c`-key-missing JSON is left untouched; field updates (date, group, grade min/max, profile operator/value), `null`-value field unsetting, single/middle/all condition removal with index reindexing, and combined update+removal in one pass; the four-operator `opchange` (`&`/`|`/`!&`/`!|`) including no-op and preserve-existing cases; per-condition `showc` flips and reindexing after removal; the global `showchange` flag and its interaction with per-condition `showc`; both AND→OR and OR→AND operator transitions correctly switching between the global-`show` and per-condition-`showc` visibility mechanisms; and the full `save_module_conditions()`/`save_section_conditions()` DB-persistence path — field/`op`/`showc`/global-`show` writes, cross-course isolation (an activity or section ID from another course is silently ignored), and batched independent updates across several activities in one call |
| `local/conditions_test.php` | 35 | Reading and shaping restriction data for the report: empty/malformed/missing-`c`-key input returns no conditions (6 data-set variants); every supported condition type is parsed with its original array index preserved and all raw fields kept under `data`; nested AND/OR groups are returned as the distinct `nested` type instead of being expanded; per-condition `showc` is read with its `true` default when absent; the `groups`/`groupings`/profile-fields lookups return course-scoped id→name maps that never leak another course's groups; filter-panel section list generation with sequential section numbers; module- and section-level condition discovery (empty course, single restriction, multiple restrictions, and type deduplication for the filter dropdown); and completion-tracked activity discovery excluding modules with tracking disabled |
| `privacy/provider_test.php` | 3 | GDPR/LGPD compliance: the class implements Moodle's `null_provider` interface, and `get_reason()` returns a non-empty language-string key that actually resolves through `get_string()` |
| **Total** | **82** | |

```bash
vendor/bin/phpunit --testsuite report_unlocker
```

**Line coverage by class** (`moodle-coverage`, PHPUnit + Xdebug):

| Class | Line coverage | Method coverage |
|-------|:-------------:|:----------------:|
| `local\condition_writer` | 96.00% (96/100) | 0.00% (0/3)\* |
| `local\conditions` | 65.68% (111/169) | 70.00% (7/10) |
| `privacy\provider` | 100.00% (1/1) | 100.00% (1/1) |
| `ai\assistant` | 1.03% (3/291) | 10.00% (1/10) |
| **Overall (8 classes)** | **17.50% (211/1206)** | **21.43% (9/42)** |

\* `condition_writer`'s methods are `static`; PHPUnit's method-coverage counter does not credit
static entry points the same way, even though the lines inside them are exercised.

The overall figure is pulled down by four classes that PHPUnit does not reach at all —
`ai\service`, `external\ai_apply`, `external\ai_chat`, and `form\conditions_form` — because
they are exercised through the Behat suite and live AI-provider verification instead of unit
tests. `ai\assistant`'s own low line coverage reflects the same split: the single PHPUnit test
here only proves the class constructs correctly; the actual prompt-building and AI-response
parsing logic is covered by Behat and by manual verification against a real AI provider, not
by an isolated unit test.

### Behat — Acceptance Tests

PHPUnit covers the plugin's business logic; these scenarios exercise the actual rendered UI end
to end — things a unit test cannot see, like the filter panel, inline editing forms, and the
bulk-removal confirmation modal.

| Feature file | Scenarios | What is covered |
|---------------|----------:|----------------|
| `access.feature` | 3 | Navigation link visibility per role; the `report/unlocker:view` capability gates access to the report itself |
| `display.feature` | 5 | Empty-course message; activity listing with date conditions rendered as human-readable text; filter controls are present on the page |
| `edit.feature` | 4 | Saving with no changes is a no-op; single and selective condition removal across several activities; section-level conditions are editable the same way as module-level ones |
| `filters.feature` | 8 | Section, restriction-type, and name-search filters work independently and combined; the no-results message; the Clear filters control resets every filter at once |
| `remove_all.feature` | 5 | The bulk-removal confirmation modal shows the correct condition count for the active filter; confirming removes only the filtered set; cancelling leaves every condition intact |
| **Total** | **25** | |

```bash
php admin/tool/behat/cli/init.php
vendor/bin/behat --tags=@report_unlocker --profile=chrome
```
