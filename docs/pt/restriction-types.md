# 🌱 Referência de Tipos de Restrição

| Tipo | Descrição | Exemplo |
|------|-----------|---------|
| **Data** | Se desbloqueia/bloqueia em uma data e hora específicas. | "Disponível a partir de 2026-03-20 14:00" |
| **Grupo** | Restringe aos membros de um grupo específico do Moodle. | "Visível para: Grupo A" |
| **Agrupamento** | Restringe aos grupos dentro de um agrupamento. | "Visível para: Agrupamento 'Projeto em Equipe'" |
| **Nota** | Restringe com base na nota de um item de avaliação (mín/máx/intervalo). | "Requer nota mínima 70% no Quiz 1" |
| **Conclusão** | Restringe com base no status de conclusão de uma atividade. | "Requer: Quiz 2 concluído com aprovação" |
| **Perfil** | Restringe com base em campos de perfil do usuário (padrão ou customizados). | "Visível para: Departamento = Engenharia" |
| **PlayerHUD** | (Requer `availability_playerhud` + `block_playerhud`) Restringe pela progressão do jogador. | "Requer: Nível ≥ 5", "Deve possuir: Ovo de Dragão" |

Qualquer tipo de restrição não listado acima — de um plugin de disponibilidade de terceiros que
o Unlocker não sabe editar — ainda é exibido como somente leitura e pode ser removido como
qualquer outra condição.
