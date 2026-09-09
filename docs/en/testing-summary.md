# 🧪 Automated Tests

Unlocker ships with **82 PHPUnit test cases** and a **25-scenario Behat suite**, run on every
CI push across the full matrix (Moodle 4.5 → 5.x, PostgreSQL & MariaDB).

### PHPUnit — Unit & Integration Tests

| Test file | Cases |
|-----------|------:|
| `tests/ai/assistant_test.php` | 1 |
| `tests/local/condition_writer_test.php` | 43 |
| `tests/local/conditions_test.php` | 35 |
| `tests/privacy/provider_test.php` | 3 |
| **Total** | **82** |

```bash
vendor/bin/phpunit --testsuite report_unlocker
```

**Line coverage** (`moodle-coverage`, PHPUnit + Xdebug) is uneven by design: the two classes
that parse and persist restrictions (`local\condition_writer`, `local\conditions`) carry the
bulk of unit coverage, while the AI orchestration layer and the form/external AJAX endpoints
are exercised end to end by Behat instead. See the full breakdown for the per-class figures.

### Behat — Acceptance Tests

| Feature file | Scenarios |
|--------------|----------:|
| `access.feature` | 3 |
| `display.feature` | 5 |
| `edit.feature` | 4 |
| `filters.feature` | 8 |
| `remove_all.feature` | 5 |
| **Total** | **25** |

```bash
php admin/tool/behat/cli/init.php
vendor/bin/behat --tags=@report_unlocker --profile=chrome
```

[Full test-by-test breakdown and coverage table →]({{ '/testing.html' | relative_url }})
