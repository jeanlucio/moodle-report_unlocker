# Moodle Report Unlocker

[![Moodle Plugin CI](https://github.com/jeanlucio/moodle-report_unlocker/actions/workflows/ci.yml/badge.svg)](https://github.com/jeanlucio/moodle-report_unlocker/actions/workflows/ci.yml)
![Moodle](https://img.shields.io/badge/Moodle-4.5%2B-orange?style=flat-square&logo=moodle&logoColor=white)
![License](https://img.shields.io/badge/License-GPLv3-blue?style=flat-square)
![Status](https://img.shields.io/badge/Status-Alpha-yellow?style=flat-square)

[English](#english) | [Português](#português)

---

## English

The **Unlocker Report** is a powerful management tool for Moodle course administrators and teachers. It provides a comprehensive overview of all **access restrictions** (availability conditions) applied to course activities and resources, allowing you to view, analyze, and modify restrictions at scale.

Instead of manually editing each activity's restriction settings one by one, Unlocker centralizes all restrictions in a single, searchable, filterable dashboard — reducing configuration time and improving visibility.

---

### ✨ Features

* 📋 **Unified Dashboard:** View all activity restrictions in one place.
* 🔍 **Advanced Filtering:**
  * Filter by activity section
  * Filter by restriction type (date, group, grade, completion, profile, PlayerHUD, etc.)
  * Search by activity name
  * Combine multiple filters at once
* 🎯 **Supported Restriction Types:**
  * 📅 **Date-based** — Activities unlock/lock on specific dates and times
  * 👥 **Group-based** — Restrict by Moodle group membership
  * 👤 **Grouping-based** — Restrict by grouping
  * 📊 **Grade-based** — Restrict by activity grades (minimum, maximum, range)
  * ✅ **Completion-based** — Restrict by activity completion status
  * 🆔 **Profile-based** — Restrict by user profile fields (standard and custom fields)
  * 🎮 **PlayerHUD-based** — Restrict by player level, items, or character class (when PlayerHUD is installed)
* ✏️ **Bulk Management:**
  * Edit restrictions directly from the report
  * Delete individual restrictions
  * Remove all restrictions matching the current filter in one action
  * **Operator selector** per activity/section: choose whether the student must match *all*, *any*, *not all*, or *not any* of the listed conditions
  * **Visibility toggle per condition** (operator *all* / *not any*): control whether a hidden condition is shown greyed-out or fully invisible to students
  * **Global visibility toggle** (operator *any* / *not all*): single flag applied to all conditions in the group
* 🎨 **Readable Descriptions**: Each restriction displays a human-friendly summary (e.g., "Created after 2026-03-15 14:30")
* 💾 **Safe Modifications:** Session key verification protects against accidental bulk changes
* 📱 **Responsive Design:** Works on desktop and tablet views

---

### 🎓 Educational Use

Unlocker simplifies common pedagogical scenarios:

* **Timed Module Releases:** Ensure content unlocks on schedule; verify all dates are correct across the course.
* **Group-Based Learning:** Confirm group restrictions apply correctly to group work activities.
* **Mastery-Based Progression:** Set up grade thresholds to gate access to advanced content.
* **Activity Sequencing:** Ensure students complete prerequisites before advancing.
* **Leveled Access:** Use PlayerHUD integration to unlock content based on student progression.
* **Accessibility Audits:** Quickly verify that no unintended restrictions block access.

---

### 📦 Requirements

| Component | Version |
|-----------|---------|
| Moodle    | 4.5+    |
| PHP       | 8.1+    |

---

### 🛠️ Installation

1. Download the `.zip` file or clone this repository.
2. Extract the folder into your Moodle `report/` directory.
3. Rename the folder to `unlocker` (if necessary).
   Final path: `your-moodle/report/unlocker/`
4. Visit **Site administration > Notifications** to complete installation.

---

### 📖 Usage

#### Accessing the Report

1. Navigate to a course (as a teacher or administrator).
2. In the left-side navigation menu, under **Course administration**, click **Unlocker**.
   (The link appears only for users with the `report/unlocker:view` capability.)

#### Understanding the Dashboard

* **Activity List:** All activities and resources in the course are listed with their current restrictions.
* **Restriction Display:** Each activity shows all applied conditions in a human-readable format.
* **Filter Panel:** At the top:
  * **Search box:** Type to filter by activity name (real-time)
  * **Section dropdown:** Show restrictions only in a specific section
  * **Restriction Type dropdown:** Show only a specific type of condition
* **Action Buttons:**
  * **Edit (✏️):** Modify a restriction (opens the Moodle availability dialog)
  * **Delete (🗑️):** Remove a single restriction
  * **Delete All Visible:** Remove all restrictions currently shown after applying filters

#### Common Workflows

**Scenario 1: Find all date-based restrictions expiring soon**

1. Set **Restriction Type** filter to `Date`.
2. Review all date-based conditions across the course.
3. Identify restrictions ending before your course deadline.
4. Edit dates as needed.

**Scenario 2: Remove all group-based restrictions for a section**

1. Select the target **Section** from the dropdown.
2. Set **Restriction Type** filter to `Group`.
3. Click **Delete All Visible** (with confirmation).

**Scenario 3: Audit player progression gating**

1. If PlayerHUD is installed, set **Restriction Type** filter to `PlayerHUD`.
2. Review all level-based, item-based, and character-class restrictions.
3. Verify that the progression makes pedagogical sense.

---

### 🌱 Restriction Types Reference

| Type | Description | Example |
|------|-------------|---------|
| **Date** | Unlocks/locks at a specific date and time. | "Available after 2026-03-20 14:00" |
| **Group** | Restricts to members of a specific Moodle group. | "Visible to: Group A" |
| **Grouping** | Restricts to groups within a grouping. | "Visible to: Grouping 'Team Project'" |
| **Grade** | Restricts based on a grade item's score (min/max/range). | "Requires minimum 70% in Quiz 1" |
| **Completion** | Restricts based on activity completion status. | "Requires: Quiz 2 completed with pass" |
| **Profile** | Restricts based on user profile fields (standard or custom). | "Visible to: Department = Engineering" |
| **PlayerHUD** | (Requires `block_playerhud`) Restricts by player progression. | "Requires: Level ≥ 5", "Must own: Dragon Egg" |

---

### 🔐 Security & Compliance

* **Capability-based access control:** Only users with `report/unlocker:view` can access the report.
* **Session key verification:** All modifications (edit, delete) require a valid session key.
* **Server-side validation:** Restriction changes are validated against the course context.
* **Audit-friendly:** No bulk deletions without explicit user confirmation.

---

### 🧪 Automated Tests

Unlocker includes a comprehensive test suite covering business logic (PHPUnit) and end-to-end acceptance (Behat).

#### PHPUnit — Unit & Integration Tests

| Test file | Cases | What is covered |
|-----------|------:|----------------|
| `locallib_parse_test.php` | 21 | Condition parsing: all supported types, `showc` per-condition value, missing `showc` defaults |
| `locallib_apply_test.php` | 28 | Field updates, removals, `opchange` (all 4 operators), `showcupdates`, `showchange`, op-mode transitions |
| `locallib_save_test.php` | 13 | DB persistence: field updates, `op`, per-condition `showc`, global `show`, op transitions, cross-course isolation |
| `locallib_integration_test.php` | 6 | Cross-type integration: mixed restriction types in same activity, edge cases |
| `privacy_provider_test.php` | 2 | GDPR compliance: plugin declares no user data retention |
| **Total** | **70** | |

```bash
vendor/bin/phpunit --testsuite report_unlocker
```

#### Behat — Acceptance Tests

| Feature file | Scenarios | What is covered |
|--------------|----------:|----------------|
| `access.feature` | 4 | Navigation link visibility per role; `editconditions` capability enforcement on form submit |
| `display.feature` | 5 | Empty-course message; activity listing with date conditions; Allow from / Restrict until labels; filter controls presence |
| `filters.feature` | 8 | Section, type and search filters independently and combined; no-results message; Clear filters button visibility and reset |
| `remove_all.feature` | 5 | Modal confirmation flow; condition count in modal body; filter-scoped bulk removal; cancel leaves conditions intact |
| `edit.feature` | 7 | Save without changes; single condition removal; selective removal across activities; section conditions; capability denial on submit |
| **Total** | **29** | |

```bash
vendor/bin/moodle-plugin-ci behat --profile chrome
```

---

### 🤝 Integration with Other Plugins

#### PlayerHUD Block

If **PlayerHUD** (`block_playerhud`) is installed, Unlocker detects and displays PlayerHUD-based restrictions:

* **Level restrictions:** Show required player level
* **Item restrictions:** Show required items and quantities
* **Character class restrictions:** Show restricted character classes

The integration is automatic — no additional configuration needed.

---

### 📄 Language & Internationalization

Unlocker includes language strings in:

* **English** (`en/`)
* **Português (Brasil)** (`pt_br/`)

All restriction descriptions are translated and displayed in the user's language.

---

### 🛣️ Roadmap

| Version | Features | Status |
|---------|----------|--------|
| **0.2.0** | Core filters, date/group/grouping/grade/completion/profile/playerhud support, bulk delete | ✅ Done |
| **0.3.0** | Behat acceptance tests (29 scenarios across 5 feature files) | ✅ Done |
| **0.4.0** | Operator selector and per-condition / global visibility toggle | ✅ Done |
| **0.5.0** | Nested restriction groups (read-only display) | Planned |
| **1.0.0** | Stable release, full edit UI for all restriction types | Planned |

---

### 🐛 Known Limitations

* **Nested restriction groups:** Currently displayed as read-only. Full editing of logical AND/OR groups requires a tree UI (planned for v1.0).
* **Backup/Restore:** Not currently supported in backup files (planned for v1.0).

---

### ⚖️ Compatibility

Unlocker is compatible with:

* ✅ Moodle 4.5+
* ✅ PostgreSQL, MariaDB, MySQL
* ✅ PHP 8.1, 8.2, 8.3+
* ✅ PlayerHUD (`block_playerhud`) — optional integration
* ✅ Custom course modules and activities

---

### 📞 Support & Contribution

For bug reports, feature requests, or contributions:

👉 GitHub Issues: https://github.com/jeanlucio/moodle-report_unlocker/issues

---

### 📝 Changelog

See [CHANGES.md](CHANGES.md) for release notes and version history.

---

## Português

O **Relatório Unlocker** é uma ferramenta poderosa para administradores de cursos e professores do Moodle. Oferece uma visão completa de todas as **restrições de acesso** (condições de disponibilidade) aplicadas às atividades e recursos do curso, permitindo visualizar, analisar e modificar restrições em escala.

Em vez de editar manualmente as configurações de restrição de cada atividade uma por uma, Unlocker centraliza todas as restrições em um único painel de controle pesquisável e filtrável — reduzindo tempo de configuração e melhorando a visibilidade.

---

### ✨ Funcionalidades

* 📋 **Painel Unificado:** Visualize todas as restrições de atividades em um único lugar.
* 🔍 **Filtros Avançados:**
  * Filtrar por seção da atividade
  * Filtrar por tipo de restrição (data, grupo, nota, conclusão, perfil, PlayerHUD, etc.)
  * Buscar por nome da atividade
  * Combinar múltiplos filtros simultaneamente
* 🎯 **Tipos de Restrição Suportados:**
  * 📅 **Baseada em data** — Atividades se desbloqueiam/bloqueiam em datas e horas específicas
  * 👥 **Baseada em grupo** — Restringe por grupo do Moodle
  * 👤 **Baseada em agrupamento** — Restringe por agrupamento
  * 📊 **Baseada em nota** — Restringe por notas de atividades (mínima, máxima, intervalo)
  * ✅ **Baseada em conclusão** — Restringe por status de conclusão de atividades
  * 🆔 **Baseada em perfil** — Restringe por campos de perfil do usuário (padrão ou customizados)
  * 🎮 **Baseada em PlayerHUD** — Restringe por nível, itens ou classe de personagem (quando PlayerHUD está instalado)
* ✏️ **Gerenciamento em Massa:**
  * Edite restrições diretamente no relatório
  * Exclua restrições individuais
  * Remova todas as restrições que correspondem ao filtro atual em uma ação
* 🎨 **Descrições Legíveis:** Cada restrição exibe um resumo amigável (ex: "Disponível após 2026-03-15 14:30")
* 💾 **Modificações Seguras:** Verificação de chave de sessão protege contra mudanças acidentais em massa
* 📱 **Design Responsivo:** Funciona em visualizações de desktop e tablet

---

### 🎓 Uso Educacional

Unlocker simplifica cenários pedagógicos comuns:

* **Lançamento de Módulos Cronometrados:** Garanta que o conteúdo se desbloqueie conforme o cronograma; verifique se todas as datas estão corretas no curso.
* **Aprendizagem Baseada em Grupos:** Confirme se as restrições de grupo se aplicam corretamente às atividades em grupo.
* **Progressão Baseada em Domínio:** Configure limites de notas para controlar o acesso ao conteúdo avançado.
* **Sequenciamento de Atividades:** Garanta que os estudantes concluam pré-requisitos antes de avançar.
* **Acesso Nivelado:** Use a integração PlayerHUD para desbloquear conteúdo com base na progressão do estudante.
* **Auditorias de Acessibilidade:** Verifique rapidamente que nenhuma restrição não intencional bloqueie o acesso.

---

### 📦 Requisitos

| Componente | Versão |
|-----------|--------|
| Moodle    | 4.5+   |
| PHP       | 8.1+   |

---

### 🛠️ Instalação

1. Baixe o arquivo `.zip` ou clone este repositório.
2. Extraia a pasta no diretório `report/` do Moodle.
3. Renomeie a pasta para `unlocker` (se necessário).
   Caminho final: `seu-moodle/report/unlocker/`
4. Acesse **Administração do site > Notificações** para completar a instalação.

---

### 📖 Uso

#### Acessando o Relatório

1. Navegue para um curso (como professor ou administrador).
2. No menu de navegação esquerdo, em **Administração do curso**, clique em **Unlocker**.
   (O link aparece apenas para usuários com a capacidade `report/unlocker:view`.)

#### Entendendo o Painel

* **Lista de Atividades:** Todas as atividades e recursos do curso são listados com suas restrições atuais.
* **Exibição de Restrições:** Cada atividade mostra todas as condições aplicadas em um formato legível.
* **Painel de Filtros:** No topo:
  * **Caixa de busca:** Digite para filtrar por nome da atividade (tempo real)
  * **Menu suspenso Seção:** Mostre restrições apenas em uma seção específica
  * **Menu suspenso Tipo de Restrição:** Mostre apenas um tipo específico de condição
* **Botões de Ação:**
  * **Editar (✏️):** Modifique uma restrição (abre o diálogo de disponibilidade do Moodle)
  * **Excluir (🗑️):** Remova uma restrição individual
  * **Excluir Todos os Visíveis:** Remova todas as restrições atualmente exibidas após aplicar filtros

#### Fluxos de Trabalho Comuns

**Cenário 1: Encontre todas as restrições de data que expiram em breve**

1. Defina o filtro **Tipo de Restrição** como `Data`.
2. Analise todas as condições baseadas em data no curso.
3. Identifique restrições terminando antes de sua data limite do curso.
4. Edite as datas conforme necessário.

**Cenário 2: Remova todas as restrições baseadas em grupo para uma seção**

1. Selecione a **Seção** alvo no menu suspenso.
2. Defina o filtro **Tipo de Restrição** como `Grupo`.
3. Clique em **Excluir Todos os Visíveis** (com confirmação).

**Cenário 3: Audite a progressão gating do player**

1. Se PlayerHUD estiver instalado, defina o filtro **Tipo de Restrição** como `PlayerHUD`.
2. Analise todas as restrições baseadas em nível, itens e classe de personagem.
3. Verifique se a progressão faz sentido pedagógico.

---

### 🌱 Referência de Tipos de Restrição

| Tipo | Descrição | Exemplo |
|------|-----------|---------|
| **Data** | Se desbloqueia/bloqueia em uma data e hora específicas. | "Disponível a partir de 2026-03-20 14:00" |
| **Grupo** | Restringe aos membros de um grupo específico do Moodle. | "Visível para: Grupo A" |
| **Agrupamento** | Restringe aos grupos dentro de um agrupamento. | "Visível para: Agrupamento 'Projeto em Equipe'" |
| **Nota** | Restringe com base na nota de um item de avaliação (mín/máx/intervalo). | "Requer nota mínima 70% no Quiz 1" |
| **Conclusão** | Restringe com base no status de conclusão de uma atividade. | "Requer: Quiz 2 concluído com aprovação" |
| **Perfil** | Restringe com base em campos de perfil do usuário (padrão ou customizados). | "Visível para: Departamento = Engenharia" |
| **PlayerHUD** | (Requer `block_playerhud`) Restringe pela progressão do jogador. | "Requer: Nível ≥ 5", "Deve possuir: Ovo de Dragão" |

---

### 🔐 Segurança e Conformidade

* **Controle de acesso baseado em capacidades:** Apenas usuários com a capacidade `report/unlocker:view` podem acessar o relatório.
* **Verificação de chave de sessão:** Todas as modificações (editar, excluir) exigem uma chave de sessão válida.
* **Validação no servidor:** As mudanças de restrição são validadas com base no contexto do curso.
* **Auditoria amigável:** Sem exclusões em massa sem confirmação explícita do usuário.

---

### 🧪 Testes Automatizados

Unlocker inclui uma suíte de testes abrangente cobrindo lógica de negócio (PHPUnit) e aceitação ponta a ponta (Behat).

#### PHPUnit — Testes Unitários e de Integração

| Arquivo de teste | Casos | O que é coberto |
|------------------|------:|-----------------|
| `locallib_parse_test.php` | 21 | Análise de condições: todos os tipos suportados, valor `showc` por condição, padrões quando `showc` está ausente |
| `locallib_apply_test.php` | 28 | Atualização de campos, remoções, `opchange` (4 operadores), `showcupdates`, `showchange`, transições de modo |
| `locallib_save_test.php` | 13 | Persistência no BD: atualizações, campo `op`, `showc` por condição, `show` global, transições, isolamento entre cursos |
| `locallib_integration_test.php` | 6 | Integração entre tipos: tipos de restrição mistos na mesma atividade, casos extremos |
| `privacy_provider_test.php` | 2 | Conformidade GDPR: plugin declara nenhuma retenção de dados do usuário |
| **Total** | **70** | |

```bash
vendor/bin/phpunit --testsuite report_unlocker
```

#### Behat — Testes de Aceitação

| Feature file | Cenários | O que é coberto |
|--------------|----------:|----------------|
| `access.feature` | 4 | Visibilidade do link de navegação por papel; enforcement da capacidade `editconditions` no envio do formulário |
| `display.feature` | 5 | Mensagem de curso vazio; listagem de atividades com condições de data; labels Allow from / Restrict until; presença dos filtros |
| `filters.feature` | 8 | Filtros de seção, tipo e busca individualmente e combinados; mensagem sem resultados; botão Limpar filtros |
| `remove_all.feature` | 5 | Fluxo de confirmação modal; contagem de condições no modal; remoção em massa respeitando filtro ativo; cancelar preserva condições |
| `edit.feature` | 7 | Salvar sem alterações; remoção de condição individual; remoção seletiva entre atividades; condições de seção; negação de capacidade |
| **Total** | **29** | |

```bash
vendor/bin/moodle-plugin-ci behat --profile chrome
```

---

### 🤝 Integração com Outros Plugins

#### Block PlayerHUD

Se **PlayerHUD** (`block_playerhud`) estiver instalado, Unlocker detecta e exibe restrições baseadas em PlayerHUD:

* **Restrições de nível:** Exibe nível de jogador necessário
* **Restrições de item:** Exibe itens necessários e quantidades
* **Restrições de classe de personagem:** Exibe classes de personagem restritas

A integração é automática — nenhuma configuração adicional necessária.

---

### 📄 Idioma e Internacionalização

Unlocker inclui strings de idioma em:

* **English** (`en/`)
* **Português (Brasil)** (`pt_br/`)

Todas as descrições de restrição são traduzidas e exibidas no idioma do usuário.

---

### 🛣️ Roadmap

| Versão | Funcionalidades | Status |
|--------|-----------------|--------|
| **0.2.0** | Filtros principais, suporte data/grupo/agrupamento/nota/conclusão/perfil/playerhud, exclusão em massa | ✅ Concluído |
| **0.3.0** | Testes de aceitação Behat (29 cenários em 5 feature files) | ✅ Concluído |
| **0.4.0** | Seletor de operador e alternador de visibilidade por condição / global | ✅ Concluído |
| **0.5.0** | Grupos de restrição aninhados (somente leitura) | Planejado |
| **1.0.0** | Versão estável, UI de edição completa para todos os tipos de restrição | Planejado |

---

### 🐛 Limitações Conhecidas

* **Grupos de restrição aninhados:** Atualmente exibidos como somente leitura. A edição completa de grupos lógicos AND/OR requer uma UI de árvore (planejada para v1.0).
* **Backup/Restauração:** Não atualmente suportado em arquivos de backup (planejado para v1.0).

---

### ⚖️ Compatibilidade

Unlocker é compatível com:

* ✅ Moodle 4.5+
* ✅ PostgreSQL, MariaDB, MySQL
* ✅ PHP 8.1, 8.2, 8.3+
* ✅ PlayerHUD (`block_playerhud`) — integração opcional
* ✅ Módulos e atividades de curso personalizados

---

### 📞 Suporte e Contribuição

Para relatos de bugs, solicitações de funcionalidades ou contribuições:

👉 GitHub Issues: https://github.com/jeanlucio/moodle-report_unlocker/issues

---

### 📝 Changelog

Veja [CHANGES.md](CHANGES.md) para notas de versão e histórico.
