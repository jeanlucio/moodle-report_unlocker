# 🤝 Integrações

### PlayerHUD

Se **`availability_playerhud`** e **`block_playerhud`** estiverem instalados, Unlocker detecta
e exibe restrições baseadas em PlayerHUD:

* `availability_playerhud` registra o tipo de condição `playerhud` no sistema de
  disponibilidade do Moodle.
* `block_playerhud` armazena os dados de itens e classes referenciados pela condição.

O Unlocker lê os nomes de itens e classes das tabelas do `block_playerhud` para preencher os
seletores de edição. A integração é automática — nenhuma configuração adicional necessária.

### Assistente IA

O assistente IA fica disponível quando uma das seguintes opções está configurada:

* **`core_ai`** (Moodle 4.5+) — configure um provedor de IA em *Administração do site → IA →
  Provedores de IA*.
* **`local_aihub`** — fornece chaves de API de IA (BYOK, pessoal e do site).

Com um provedor ativo, o botão do chat IA aparece no relatório. Descreva em linguagem natural
as mudanças desejadas; o assistente exibe uma prévia de todas as modificações e só as aplica
após sua confirmação.
