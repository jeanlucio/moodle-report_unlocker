# 🧪 Testes Automatizados

Unlocker inclui **82 casos de teste PHPUnit** e uma suíte Behat com **25 cenários**, executados
em todo push de CI na matriz completa (Moodle 4.5 → 5.x, PostgreSQL e MariaDB).

### PHPUnit — Testes Unitários e de Integração

| Arquivo de teste | Casos | O que é coberto |
|-------------------|------:|------------------|
| `ai/assistant_test.php` | 1 | A classe do assistente pode ser construída a partir do conjunto de condições de um curso real sem erro — um teste de fumaça; o ciclo de requisição/resposta de IA em si é exercitado pela suíte Behat e por verificação manual contra um provedor real, não mockado aqui |
| `local/condition_writer_test.php` | 43 | A lógica pura de análise e reescrita por trás de toda edição inline e remoção em massa: JSON malformado/vazio/sem a chave `c` permanece intocado; atualizações de campo (data, grupo, nota mín/máx, operador/valor de perfil), desabilitação de campo via valor `null`, remoção de condição única/do meio/de todas com reindexação; atualização e remoção combinadas em uma única chamada; o `opchange` com os quatro operadores (`&`/`|`/`!&`/`!|`), incluindo casos de no-op e preservação do valor existente; alternância de `showc` por condição e reindexação após remoção; a flag global `showchange` e sua interação com o `showc` por condição; as transições de operador AND→OR e OR→AND alternando corretamente entre os mecanismos de visibilidade `show` global e `showc` por condição; e o caminho completo de persistência em BD via `save_module_conditions()`/`save_section_conditions()` — escrita de campo/`op`/`showc`/`show` global, isolamento entre cursos (um ID de atividade ou seção de outro curso é ignorado silenciosamente), e atualizações independentes em lote em várias atividades numa única chamada |
| `local/conditions_test.php` | 35 | Leitura e formatação dos dados de restrição para o relatório: entrada vazia/malformada/sem a chave `c` não retorna condições (6 variantes de data set); cada tipo de condição suportado é analisado preservando seu índice original no array e mantendo todos os campos brutos em `data`; grupos aninhados AND/OR são retornados como o tipo distinto `nested` em vez de expandidos; o `showc` por condição é lido com seu padrão `true` quando ausente; as buscas de `groups`/`groupings`/campos de perfil retornam mapas id→nome escopados ao curso que nunca vazam grupos de outro curso; geração da lista de seções do painel de filtros com números sequenciais; descoberta de condições em nível de módulo e de seção (curso vazio, restrição única, múltiplas restrições, e deduplicação de tipos para o menu de filtro); e descoberta de atividades com rastreamento de conclusão, excluindo módulos com rastreamento desabilitado |
| `privacy/provider_test.php` | 3 | Conformidade GDPR/LGPD: a classe implementa a interface `null_provider` do Moodle, e `get_reason()` retorna uma chave de string de idioma não vazia que de fato resolve via `get_string()` |
| **Total** | **82** | |

```bash
vendor/bin/phpunit --testsuite report_unlocker
```

**Cobertura de linhas por classe** (`moodle-coverage`, PHPUnit + Xdebug):

| Classe | Cobertura de linhas | Cobertura de métodos |
|--------|:--------------------:|:----------------------:|
| `local\condition_writer` | 96,00% (96/100) | 0,00% (0/3)\* |
| `local\conditions` | 65,68% (111/169) | 70,00% (7/10) |
| `privacy\provider` | 100,00% (1/1) | 100,00% (1/1) |
| `ai\assistant` | 1,03% (3/291) | 10,00% (1/10) |
| **Geral (8 classes)** | **17,50% (211/1206)** | **21,43% (9/42)** |

\* Os métodos de `condition_writer` são `static`; o contador de cobertura de métodos do
PHPUnit não credita os pontos de entrada estáticos da mesma forma, mesmo com as linhas
internas sendo exercitadas.

O número geral é puxado para baixo por quatro classes que o PHPUnit nunca alcança —
`ai\service`, `external\ai_apply`, `external\ai_chat` e `form\conditions_form` — porque são
exercitadas pela suíte Behat e por verificação manual com um provedor de IA real, não por
testes unitários. A baixa cobertura de linhas de `ai\assistant` reflete a mesma divisão: o
único teste PHPUnit aqui só comprova que a classe é construída corretamente; a lógica real de
construção de prompt e análise da resposta da IA é coberta pelo Behat e por verificação
manual, não por um teste unitário isolado.

### Behat — Testes de Aceitação

O PHPUnit cobre a lógica de negócio do plugin; estes cenários exercitam a interface renderizada
de ponta a ponta — coisas que um teste unitário não enxerga, como o painel de filtros, os
formulários de edição inline e o modal de confirmação de remoção em massa.

| Feature file | Cenários | O que é coberto |
|---------------|---------:|------------------|
| `access.feature` | 3 | Visibilidade do link de navegação por papel; a capacidade `report/unlocker:view` controla o acesso ao próprio relatório |
| `display.feature` | 5 | Mensagem de curso vazio; listagem de atividades com condições de data renderizadas como texto legível; presença dos controles de filtro na página |
| `edit.feature` | 4 | Salvar sem alterações é um no-op; remoção de condição individual e seletiva entre várias atividades; condições em nível de seção são editáveis da mesma forma que as de módulo |
| `filters.feature` | 8 | Filtros de seção, tipo de restrição e busca por nome funcionam individualmente e combinados; mensagem sem resultados; o controle Limpar filtros reseta todos os filtros de uma vez |
| `remove_all.feature` | 5 | O modal de confirmação de remoção em massa mostra a contagem correta de condições para o filtro ativo; confirmar remove apenas o conjunto filtrado; cancelar preserva todas as condições |
| **Total** | **25** | |

```bash
php admin/tool/behat/cli/init.php
vendor/bin/behat --tags=@report_unlocker --profile=chrome
```
