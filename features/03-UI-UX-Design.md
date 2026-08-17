(NÃO PULE)
Leia os documentos a seguir antes de fazer qualquer alteração:
@context.md
@workflow.md
@ui-design.md
...

Você está na fase de refinamento visual e qualidade do SIAETEC.
Leia o ui-design.md completo antes de começar — ele é a fonte
de verdade para todas as decisões visuais deste prompt.

Não altere regras de negócio, queries SQL, lógica de sessão
ou estrutura de banco. Apenas CSS, HTML e JS de apresentação.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PASSO 1 — CORREÇÕES VISUAIS PRIORITÁRIAS (home.php)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Problemas identificados visualmente na aluno/home.php:

[ 1.1 ] Hero flutuando no centro com muito espaço vazio
  → O cartão deve ocupar mais da viewport, não ficar pequeno
    e centralizado em uma área cinza enorme
  → Solução: o hero deve ter largura máxima de 1100px,
    margin: 0 auto, e padding vertical de 2rem no container
    — não centrado verticalmente como se fosse um modal
  → No mobile: padding lateral de 1rem, sem espaço excessivo

[ 1.2 ] Imagem da refeição sem border-radius no lado direito
  → Aplicar border-radius: var(--raio-borda-grande) na imagem
  → object-fit: cover, height: 320px desktop / 220px mobile
  → A imagem não deve ser cortada abruptamente dentro do cartão

[ 1.3 ] Botão "ENQUETE →" estreito
  → Aplicar width: 100% conforme ui-design.md
  → Padding: 0.875rem 1.5rem, font-weight: 600
  → Deve ocupar toda a largura da coluna esquerda

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PASSO 2 — ESCALA VISUAL GLOBAL (+25%)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

O sistema ficou visualmente melhor em zoom 125%.
Implementar o aumento de escala de forma correta via CSS:

  → Em assets/css/style.css, alterar a base tipográfica:
    html { font-size: 18px; } /* era 16px — escala todos os rem */

  → NÃO usar zoom, transform: scale() ou qualquer hack visual
  → Como o projeto usa rem consistentemente, esse único ajuste
    propaga o aumento para textos, botões, campos e espaçamentos

  → Ajustar a navbar para escala elástica:
    - Trocar height: 64px por min-height: 64px
    - Logos: max-height: 44px (era 40px)
    - Botões de hambúrguer e perfil: mínimo 48px × 48px
      (área de toque acessível — obrigatório no mobile)
    - Breakpoint do hambúrguer: ativar em ≤ 900px (era 768px)
      para acomodar zoom + links do admin sem compressão
    - Links da navbar admin: usar gap menor e font-size: 0.9rem
      se necessário para caber em telas menores

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PASSO 3 — CENTRALIZAR CSS E JS GLOBAIS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Hoje há <style> inline em praticamente todas as páginas.
Isso quebra cache, dificulta manutenção e gera inconsistência.

[ 3.1 ] assets/css/style.css
  → Mover para cá todos os estilos repetidos entre páginas:
    - Estilos de tabela (.tabela-admin, thead, tbody, tr, td)
    - Selos de status (.selo-ativo, .selo-inativo, .selo-aberta,
      .selo-encerrada)
    - Grid de cards do dashboard (.grade-cards, .cartao-resumo)
    - Estilos de formulário (.grupo-campo, label, input, select,
      textarea, .mensagem-erro, .mensagem-sucesso)
    - Cabeçalho de página admin (.cabecalho-pagina, h1 de seção)
    - Container responsivo de tabela (.container-tabela com
      overflow-x: auto para scroll horizontal no mobile)
  → Remover os <style> inline das páginas após mover

[ 3.2 ] assets/js/main.js
  → Centralizar aqui os comportamentos comuns já implementados:
    - Lógica do drawer mobile (hambúrguer + overlay + fechar)
    - Dropdown de perfil (abrir/fechar ao clicar)
    - Fechamento ao clicar fora (drawer e dropdown)
  → Remover scripts duplicados das páginas após centralizar
  → Manter defer no carregamento em todas as páginas

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PASSO 4 — TABELAS RESPONSIVAS NO ADMIN
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Aplicar em: admin/alunos.php, admin/turmas.php,
admin/refeicoes.php, admin/enquetes.php, admin/resumo.php

  → Envolver cada <table> em:
    <div class="container-tabela"> ... </div>

  → CSS em style.css:
    .container-tabela {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: var(--raio-borda-medio);
    }

  → Garantir white-space: nowrap nas células de ação
    para botões não quebrarem em duas linhas

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PASSO 5 — CAMPO DE DATA/HORA NA ENQUETE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Problema: o campo datetime-local exibe mm/dd/yy (formato
americano) e permite inconsistência entre data da enquete
e horário limite.

Solução em admin/enquetes.php (apenas frontend + PHP):
  → Separar em dois campos HTML:
    <input type="date" name="data_enquete">
    <input type="time" name="horario_limite">

  → No PHP que processa o POST, montar o DATETIME:
    $horario_limite = $_POST['data_enquete'] . ' '
                    . $_POST['horario_limite'] . ':00';

  → Sem alteração no banco — a coluna horario_limite
    continua DATETIME, só muda como o valor é montado
  → Validar server-side que horario_limite > data_enquete 00:00

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PASSO 6 — SUBSTITUIR confirm() POR MODAL
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Aplicar em todas as ações destrutivas do admin:
inativar, reativar e excluir registros.

  → Criar um modal de confirmação reutilizável em CSS puro,
    incluído via includes/navbar-admin.php (já presente em
    todas as páginas admin):

    <div id="modal-confirmacao" class="modal-overlay" hidden>
      <div class="modal-caixa">
        <p id="modal-mensagem"></p>
        <div class="modal-acoes">
          <button id="modal-confirmar" class="botao-primario">
            Confirmar
          </button>
          <button id="modal-cancelar" class="botao-secundario">
            Cancelar
          </button>
        </div>
      </div>
    </div>

  → JS em main.js: função abrirModal(mensagem, urlAcao)
    que popula o modal e redireciona para urlAcao ao confirmar
  → CSS do modal em style.css:
    overlay com position: fixed, fundo rgba escuro,
    caixa centralizada com var(--raio-borda-medio) e
    var(--sombra-cartao)
  → Substituir todos os onclick="return confirm(...)" por
    onclick="abrirModal('Mensagem aqui', 'url?acao=...')"

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PASSO 7 — AJUSTES DE PERFORMANCE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[ 7.1 ] Logos da navbar
  → Remover loading="lazy" das logos em navbar-aluno.php
    e navbar-admin.php — ficam acima da dobra e devem
    carregar imediatamente

[ 7.2 ] Preview de imagem de refeição
  → Em admin/refeicoes.php, adicionar preview client-side:
    quando o admin digitar o nome do arquivo no campo imagem,
    mostrar um <img> de preview abaixo do campo apontando
    para assets/img/refeicoes/<valor_digitado>
  → Se a imagem não existir (onerror), mostrar o fallback
    prato-padrao.webp com aviso "Arquivo não encontrado"
  → Apenas JS puro no evento input do campo

[ 7.3 ] .htaccess com GZIP
  → Criar arquivo .htaccess na raiz do projeto:

    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/css application/javascript
    </IfModule>

    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType text/css "access plus 1 week"
        ExpiresByType application/javascript "access plus 1 week"
        ExpiresByType image/webp "access plus 1 month"
    </IfModule>

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REGRAS INVIOLÁVEIS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

- CSS3 puro — sem Bootstrap, Tailwind ou qualquer framework
- JS puro — sem jQuery ou bibliotecas adicionais
- Chart.js permanece apenas em grafico.js e dashboard.php
- Não alterar lógica PHP, queries SQL ou regras de negócio
- Variáveis CSS sempre em português via var(--nome)
- Não criar config.php

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ENTREGÁVEL
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Execute os passos em ordem. Pode seguir sem parar a cada um.
Ao concluir, no arquivo @log.md, liste todos os arquivos modificados e sinalize
qualquer inconsistência visual encontrada que não estava
no escopo deste prompt.