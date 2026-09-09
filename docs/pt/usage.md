# 📖 Como Usar

#### Acessando o Relatório

1. Navegue até um curso (como professor ou administrador).
2. No menu de navegação esquerdo, em **Administração do curso**, clique em **Unlocker**.
   (O link aparece apenas para usuários com a capacidade `report/unlocker:view`.)

#### Entendendo o Painel

* **Lista de Atividades:** Todas as atividades e recursos do curso são listados com suas
  restrições atuais.
* **Exibição de Restrições:** Cada atividade mostra todas as condições aplicadas em um formato
  legível.
* **Painel de Filtros:** No topo:
  * **Caixa de busca:** Digite para filtrar por nome da atividade (tempo real)
  * **Menu suspenso Seção:** Mostre restrições apenas em uma seção específica
  * **Menu suspenso Tipo de Restrição:** Mostre apenas um tipo específico de condição
* **Edição inline:** Cada restrição é editável diretamente no relatório — altere datas,
  selecione grupos, ajuste limites de nota, etc. sem sair da página.
* **Checkbox de remoção:** Marque restrições individuais para remoção; clique em **Salvar
  todas as alterações** uma única vez para persistir todas as edições e exclusões juntas.
* **Excluir todas as visíveis:** Marca todas as restrições visíveis no momento (respeitando os
  filtros ativos) para remoção e salva automaticamente após uma caixa de confirmação.

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
