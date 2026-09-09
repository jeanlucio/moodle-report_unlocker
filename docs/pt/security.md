# 🔐 Segurança e Conformidade

* **Controle de acesso baseado em capacidades:** Apenas usuários com a capacidade
  `report/unlocker:view` podem acessar o relatório.
* **Verificação de chave de sessão:** Todas as modificações (editar, excluir) exigem uma chave
  de sessão válida.
* **Validação no servidor:** As mudanças de restrição são validadas com base no contexto do
  curso.
* **Auditoria amigável:** Sem exclusões em massa sem confirmação explícita do usuário.
* **Conformidade com privacidade:** Este plugin não armazena dados pessoais de usuários.
  Implementa a Privacy API do Moodle (`null_provider`) e está em total conformidade com a
  LGPD/GDPR.

### 🤖 Assistente IA

O assistente IA opcional só é ativado quando o `core_ai` do próprio Moodle (4.5+) ou o plugin
[AI Hub](https://moodle.org/plugins/local_aihub) está configurado com uma chave de provedor.
Ele envia apenas o pedido de edição em linguagem natural do professor — nunca dados de
estudantes — gera uma prévia e não aplica nada sem confirmação explícita.
