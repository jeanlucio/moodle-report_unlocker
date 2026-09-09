# ✨ Funcionalidades

* 📋 **Painel Unificado:** Visualize todas as restrições de atividades em um único lugar.
* 🔍 **Filtros Avançados:**
  * Filtrar por seção da atividade
  * Filtrar por tipo de restrição (data, grupo, nota, conclusão, perfil, PlayerHUD, etc.)
  * Buscar por nome da atividade
  * Combinar múltiplos filtros simultaneamente
* 🎯 **Tipos de Restrição Suportados (edição inline):**
  * 📅 **Baseada em data** — Atividades se desbloqueiam/bloqueiam em datas e horas específicas
  * 👥 **Baseada em grupo** — Restringe por grupo do Moodle
  * 👤 **Baseada em agrupamento** — Restringe por agrupamento
  * 📊 **Baseada em nota** — Restringe por notas de atividades (mínima, máxima, intervalo)
  * ✅ **Baseada em conclusão** — Restringe por status de conclusão de atividades
  * 🆔 **Baseada em perfil** — Restringe por campos de perfil do usuário (padrão ou customizados)
  * 🎮 **Baseada em PlayerHUD** — Restringe por nível, itens ou classe de personagem (requer
    `availability_playerhud` e `block_playerhud`)
  * 🔗 **Grupos de restrição aninhados** — Grupos AND/OR exibidos como **"Grupo de restrição
    aninhado (N)"** (onde `N` é o número de condições filhas). É possível ocultá-los dos
    estudantes ou abrir as configurações nativas da atividade para editar o grupo diretamente.
* ✏️ **Gerenciamento em Massa:**
  * Edite restrições diretamente no relatório
  * Exclua restrições individuais
  * Remova todas as restrições que correspondem ao filtro atual em uma ação
  * **Seletor de operador** por atividade/seção: escolha se o estudante deve corresponder a
    *todas*, *qualquer*, *não todas* ou *nenhuma* das condições listadas
  * **Controle de visibilidade por condição** (operadores *todas* / *nenhuma*): define se uma
    condição oculta aparece esmaecida ou invisível para o estudante
  * **Controle de visibilidade global** (operadores *qualquer* / *não todas*): único flag
    aplicado a todo o grupo de condições
* 🎨 **Descrições Legíveis:** Cada restrição exibe um resumo amigável (ex: "Disponível após
  2026-03-15 14:30")
* 🤖 **Assistente IA:** Descreva as mudanças desejadas em linguagem natural; o assistente exibe
  uma prévia e só aplica após confirmação. Requer `core_ai` configurado no Moodle (4.5+) ou o
  plugin `local_aihub`.
* 💾 **Modificações Seguras:** Verificação de chave de sessão protege contra mudanças acidentais
  em massa
* 📱 **Design Responsivo:** Funciona em visualizações de desktop e tablet
