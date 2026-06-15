O sistema passou por uma reestruturação de arquitetura. 
Leia este briefing completo antes de tocar em qualquer arquivo.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
O QUE MUDOU
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

A tabela `refeicoes` foi separada em duas entidades:

ANTES:
  refeicoes → titulo, descricao, data_refeicao, horario_limite
  intencoes_alimentares → aluno_id, refeicao_id

AGORA:
  refeicoes  → titulo, descricao, imagem (cadastro fixo, reutilizável)
  enquetes   → refeicao_id, data_enquete, horario_limite (uso recorrente)
              + UNIQUE KEY em data_enquete (uma enquete por dia)
  intencoes_alimentares → aluno_id, enquete_id (não mais refeicao_id)
  resumos_envio → enquete_id (não mais refeicao_id)

O banco foi recriado do zero com o novo schema.sql.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PASSO 1 — CORRIGIR BUG DO GRÁFICO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

O gráfico no dashboard cresce infinitamente a cada renderização.
Corrija assets/js/grafico.js:

- Destruir a instância anterior do Chart antes de criar uma nova:
  if (window.graficoPrincipal instanceof Chart) {
      window.graficoPrincipal.destroy();
  }
- Limitar a altura do canvas via CSS: max-height: 320px
- O gráfico só deve ser inicializado uma vez — remover qualquer
  setInterval ou loop de re-renderização se existir

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PASSO 2 — ADAPTAR TODOS OS ARQUIVOS PHP
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Adapte cada arquivo afetado pela mudança de schema.
Em cada query alterada, use prepared statements obrigatoriamente.

[ 2.1 ] aluno/home.php
  → Query antiga buscava refeicao onde data_refeicao = hoje
  → Nova query: JOIN enquetes + refeicoes onde data_enquete = CURDATE()
  → Passar horario_limite da enquete (não da refeição) para o contador

[ 2.2 ] aluno/enquete.php
  → Buscar enquete ativa de hoje via JOIN enquetes + refeicoes
  → Verificar enquete_aberta() com horario_limite da enquete
  → AJAX para api/responder.php passa enquete_id (não refeicao_id)

[ 2.3 ] api/responder.php
  → Receber e validar enquete_id (não refeicao_id)
  → Buscar enquete por id, checar enquete_aberta()
  → UPSERT em intencoes_alimentares com (aluno_id, enquete_id)

[ 2.4 ] api/resultados.php
  → Buscar totais por enquete_id
  → Retornar: sim, nao, sem_resposta (total alunos ativos − sim − nao)

[ 2.5 ] api/grafico.php
  → JOIN enquetes + refeicoes + intencoes_alimentares
  → Agrupar por data_enquete
  → Retornar: [ { data, sim, nao, sem_resposta } ]

[ 2.6 ] api/enviar-resumo.php
  → Receber enquete_id
  → Montar mensagem com dados da enquete + refeição vinculada
  → Gravar em resumos_envio com enquete_id

[ 2.7 ] admin/enquete.php
  → Buscar enquete ativa de hoje (JOIN enquetes + refeicoes)
  → Exibir título da refeição + totais via api/resultados.php

[ 2.8 ] admin/resumo.php
  → Buscar enquete de hoje com JOIN
  → Histórico de resumos_envio com JOIN enquetes + refeicoes

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PASSO 3 — REESCREVER PÁGINAS DO ADMIN
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[ 3.1 ] admin/refeicoes.php — CRUD de refeições
  → Listagem: id, título, descrição resumida, imagem (sim/não)
  → Formulário de cadastro: título, descrição, imagem (nome do .webp)
  → Sem data nem horário limite aqui — isso é da enquete
  → Ação de excluir só se a refeição não tiver enquetes vinculadas

[ 3.2 ] admin/enquetes.php — NOVO ARQUIVO (criar)
  → Listagem de enquetes: data, refeição vinculada, horário limite,
    status (aberta/encerrada calculado por enquete_aberta())
  → Formulário de criação:
    - Select de refeição cadastrada (dropdown com títulos)
    - Data da enquete (date input)
    - Horário limite (datetime-local input)
  → Validação server-side: não permitir duas enquetes na mesma data
    (já garantido pelo UNIQUE, mas tratar o erro com mensagem amigável)
  → Ação de excluir só se não houver intenções registradas

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PASSO 4 — ATUALIZAR NAVBAR DO ADMIN
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Atualizar includes/navbar-admin.php:
  → Adicionar link "Enquetes" apontando para admin/enquetes.php
  → Ordem: Dashboard · Refeições · Enquetes · Alunos · Turmas · Resumo

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REGRAS INVIOLÁVEIS (mantidas)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

- Prepared statements em 100% das queries
- auth.php no topo de toda página protegida antes de qualquer HTML
- session_start() apenas dentro de auth.php
- Variáveis CSS em português, sem hex avulso no CSS
- Scripts JS no final do <body> ou com defer
- Nunca criar config.php

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ENTREGÁVEL
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Siga a ordem dos passos. Pode executar sem parar a cada arquivo.
Ao concluir, liste tudo que foi alterado/criado e sinalize
qualquer novo bloqueador ou inconsistência encontrada.