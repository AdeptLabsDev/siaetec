Você é o agente de desenvolvimento do projeto SIAETEC (Sistema de Intenção Alimentar Escolar).

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PASSO 1 — LEITURA OBRIGATÓRIA (não pule)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Antes de escrever qualquer linha de código, leia os três documentos abaixo na ordem indicada:

1. context.md     → regras de negócio, fluxos, entidades e decisões do sistema
2. workflow.md    → stack, arquitetura híbrida, convenções e ordem de desenvolvimento
3. ui-design.md   → paleta de cores, tipografia, componentes, layout de cada tela e otimizações

Após a leitura, confirme com um resumo de 3 linhas do que será desenvolvido antes de começar.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PASSO 2 — CONTEXTO DO PROJETO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Stack: PHP puro + MySQL + HTML5 + CSS3 puro + JavaScript puro
Arquitetura: híbrida (server-side para navegação/auth + AJAX via fetch para ações pontuais)
Fonte: Inter (Google Fonts, otimizada com preconnect + display=swap)
Sem frameworks CSS ou JS — nenhuma biblioteca externa exceto Chart.js (somente no grafico.js)

A estrutura de arquivos já existe. Todos os arquivos contêm apenas um comentário de cabeçalho.
Não crie nem mova arquivos. Apenas preencha os existentes com código funcional.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PASSO 3 — ORDEM DE DESENVOLVIMENTO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Desenvolva os arquivos exatamente nesta sequência. Não avance para o próximo sem finalizar o atual.

[ 1 ] includes/db.php
      → Conexão PDO com MySQL usando as constantes de config.php
      → Lançar exceção em caso de falha de conexão
      → Retornar a instância $pdo para uso nos outros arquivos

[ 2 ] includes/auth.php
      → session_start() centralizado
      → Função verificar_sessao($tipo_esperado): valida se o usuário está logado e se o tipo bate
      → Se sessão inválida: redireciona para index.php
      → Se aluno com senha_alterada = 0: redireciona para aluno/alterar-senha.php
      → Função usuario_logado(): retorna os dados da sessão atual

[ 3 ] includes/funcoes.php
      → Função sanitizar($valor): limpa entrada do usuário (htmlspecialchars + trim)
      → Função redirecionar($caminho): encapsula header Location + exit
      → Função enquete_aberta($horario_limite): retorna true/false comparando com date('Y-m-d H:i:s')
      → Função formatar_data($data, $formato): formata datas para exibição em PT-BR

[ 4 ] assets/css/style.css
      → Variáveis :root completas em português conforme ui-design.md seção 1.1 e 1.3
      → Reset CSS mínimo (box-sizing, margin, padding, list-style)
      → Estilos globais: body, tipografia, links
      → Componentes globais reutilizáveis: .barra-navegacao, .cartao, .botao-primario, .botao-secundario
      → Media queries para os breakpoints definidos no ui-design.md seção 11
      → NÃO incluir estilos específicos de página aqui — cada página terá seu bloco <style> interno se necessário

[ 5 ] index.php
      → Se o usuário já estiver logado, redirecionar para a área correta (aluno ou admin)
      → Estrutura HTML completa com <head> otimizado: meta tags, preconnect, Google Fonts, style.css
      → Duas abas: Aluno (campos: RM + Senha) e Admin (campos: Usuário + Senha)
      → Troca de abas via JavaScript puro, sem recarregar a página
      → Formulário POST para o próprio index.php
      → PHP processa o login: valida credenciais com password_verify(), inicia sessão, redireciona
      → Mensagem de erro inline abaixo do botão em caso de falha
      → NÃO incluir navbar (página pré-login)

[ 6 ] aluno/alterar-senha.php
      → Incluir auth.php no topo (verificar sessão ativa de aluno)
      → Não redirecionar se senha_alterada = 0 (é exatamente para isso que serve esta página)
      → Campos: Nova Senha + Confirmar Nova Senha
      → Validação client-side: senhas coincidem antes do envio
      → Indicador visual de força da senha (fraca/média/forte) em JavaScript puro
      → POST para si mesmo: atualiza a senha no banco com password_hash() e senha_alterada = 1
      → Após sucesso: redirecionar para aluno/home.php

[ 7 ] aluno/home.php
      → Incluir auth.php no topo (verificar sessão de aluno)
      → Incluir navbar via include('../includes/navbar-aluno.php')
      → Buscar no banco a refeição do dia atual (data_refeicao = hoje)
      → Hero com duas colunas: conteúdo à esquerda, imagem à direita (conforme ui-design.md seção 5)
      → Contador regressivo até horario_limite em JavaScript puro (sem AJAX — horário vem do PHP)
      → Botão "ENQUETE →" leva para aluno/enquete.php
      → Estado condicional: sem refeição / enquete aberta / enquete encerrada

[ 8 ] aluno/enquete.php
      → Incluir auth.php no topo
      → Incluir navbar
      → Buscar a refeição ativa e a resposta já registrada do aluno (se houver)
      → Dois botões grandes: SIM e NÃO (altura mínima 64px, otimizados para mobile)
      → Envio via fetch (AJAX) para api/responder.php
      → Exibir confirmação inline após resposta, com opção de alterar se enquete ainda aberta
      → Bloquear interação se enquete encerrada (verificar horario_limite no PHP antes de renderizar)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REGRAS INVIOLÁVEIS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

- Variáveis CSS sempre em português, nunca valores hexadecimais avulsos no CSS
- Scripts JS sempre no final do <body> ou com atributo defer
- Imagens sempre em .webp com loading="lazy" (exceto hero), width, height e alt declarados
- auth.php incluído no topo de toda página protegida, antes de qualquer saída HTML
- session_start() apenas dentro de auth.php — nunca duplicado em outros arquivos
- Consultas ao banco sempre via PDO com prepared statements — nunca concatenação de variáveis no SQL
- Nunca criar config.php — ele é criado manualmente por cada membro do time

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ENTREGÁVEL ESPERADO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Ao concluir cada arquivo, informe:
✓ [nome do arquivo] — concluído
e aguarde confirmação antes de avançar para o próximo.