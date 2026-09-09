# 🧪 Testes Automatizados

Unlocker inclui **82 casos de teste PHPUnit** e uma suíte Behat com **25 cenários**, executados
em todo push de CI na matriz completa (Moodle 4.5 → 5.x, PostgreSQL e MariaDB).

### PHPUnit — Testes Unitários e de Integração

| Arquivo de teste | Casos |
|-------------------|------:|
| `tests/ai/assistant_test.php` | 1 |
| `tests/local/condition_writer_test.php` | 43 |
| `tests/local/conditions_test.php` | 35 |
| `tests/privacy/provider_test.php` | 3 |
| **Total** | **82** |

```bash
vendor/bin/phpunit --testsuite report_unlocker
```

A **cobertura de linhas** (`moodle-coverage`, PHPUnit + Xdebug) é intencionalmente desigual: as
duas classes que analisam e persistem restrições (`local\condition_writer`, `local\conditions`)
concentram a maior parte da cobertura unitária, enquanto a camada de orquestração de IA e os
endpoints de formulário/AJAX são testados ponta a ponta pelo Behat. Veja o detalhamento
completo para os números por classe.

### Behat — Testes de Aceitação

| Feature file | Cenários |
|--------------|---------:|
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

[Detalhamento completo teste a teste e tabela de cobertura →]({{ '/testing-pt.html' | relative_url }})
