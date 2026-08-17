# Log — Refinamento visual e qualidade (features/03-UI-UX-Design.md)

Data: 15/06/2026
Escopo: apenas CSS, HTML e JS de apresentação (+ montagem de POST/validação
autorizada no Passo 5). Regras de negócio, queries SQL, sessão e estrutura
de banco não foram alteradas.

## Arquivos modificados

### Criados
- **`.htaccess`** (raiz) — GZIP (`mod_deflate`) + cache (`mod_expires`) para
  HTML/CSS/JS/WebP. (Passo 7.3)

### Estilos e scripts globais
- **`assets/css/style.css`** — reescrito/centralizado:
  - `html { font-size: 18px; }` (escala global, Passo 2).
  - `.barra-navegacao` passou de `height` fixa para `min-height: 64px` (navbar elástica).
  - Centralizados (Passo 3.1): `.tabela-admin` (+ `thead/tbody/td`), selos
    (`.selo`, `.selo-ativo/-inativo/-aberta/-encerrada/-sim/-nao/-enviado/-erro`),
    `.grade-cards`/`.cartao-resumo` (dashboard), formulário (`.grupo-campo`,
    `.area-texto`, `.dica`), `.cabecalho-pagina`, `.admin-grid`/`.admin-form`/
    `.admin-lista`, `.acao-link` (+`.perigo`), `.acao-ver`, `.resumo-*`.
  - `.container-tabela` com `overflow-x:auto` (Passo 4).
  - Hero da home (Passo 1): `.hero` com `max-width:1100px; margin:0 auto`,
    `.conteudo-home` com padding vertical 2rem, `.hero-acao` `width:100%` +
    `padding:0.875rem 1.5rem`, `.hero-imagem` com `border-radius` grande/`cover`.
  - `.modal-overlay`/`.modal-caixa`/`.modal-acoes` (modal de confirmação, Passo 6).
  - `.img-preview`/`.img-preview-aviso` (preview de imagem, Passo 7.2).
- **`assets/js/main.js`** — antes vazio; agora centraliza (Passo 3.2): drawer
  mobile, dropdown de perfil e fechamento ao clicar fora; função global
  `abrirModal()` do modal de confirmação (Passo 6), com fechamento por
  Cancelar/overlay/Esc.

### Navbars (componentes incluídos em todas as páginas internas)
- **`includes/navbar-aluno.php`** — logos `max-height:44px`; hambúrguer e botão
  de perfil mínimo 48×48; breakpoint do hambúrguer em ≤900px; `loading="lazy"`
  removido das logos (Passo 7.1); `<script>` inline do drawer/dropdown removido
  e substituído por `assets/js/main.js` (defer).
- **`includes/navbar-admin.php`** — idem acima, mais: links com `gap` menor e
  `font-size:0.9rem` (Passo 2); HTML do modal `#modal-confirmacao` adicionado
  (Passo 6); `main.js` (defer).

### Páginas
- **`aluno/home.php`** — `<style>` inline removido (movido p/ style.css); `<main>`
  agora `conteudo conteudo-home` (Passo 1).
- **`admin/alunos.php`**, **`admin/turmas.php`** — `<style>` removido; `h1`→
  `cabecalho-pagina`; tabela→`container-tabela`+`tabela-admin`; `td` de ação com
  `celula-acao`; `confirm()` → `abrirModal('...', this)`.
- **`admin/refeicoes.php`** — idem; botão Excluir com `acao-link perigo`;
  preview client-side da imagem (Passo 7.2) com fallback `prato-padrao.webp`.
- **`admin/enquetes.php`** — idem; **Passo 5**: campo `horario_limite` agora
  `type="time"`; PHP monta `DATETIME` = `data_enquete . ' ' . horario . ':00'`;
  validação server-side `horario_limite > data 00:00`. Coluna do banco inalterada.
- **`admin/resumo.php`** — `<style>` removido; `h1`→`cabecalho-pagina`; tabela de
  histórico em `container-tabela`+`tabela-admin` (sem ação destrutiva → sem modal).
- **`admin/dashboard.php`** — `<style>` removido; `dash-cards`→`grade-cards`,
  `dash-card`→`cartao-resumo`, `h1`→`cabecalho-pagina`.
- **`admin/enquete.php`** — regras `.selo*` duplicadas removidas (centralizadas);
  estilos exclusivos da página mantidos.

## Decisão de implementação relevante
- **Modal vs. POST (Passo 6):** o exemplo do prompt sugere
  `onclick="abrirModal('msg','url?acao=...')"` (redirecionamento GET). Todas as
  ações destrutivas atuais são formulários **POST** com campos ocultos, e a regra
  inviolável proíbe alterar a lógica PHP. Para preservar o POST sem tocar no PHP,
  `abrirModal(mensagem, acao)` aceita tanto uma **URL string** (redireciona, como
  no exemplo) quanto o **próprio `<form>`** (envia via `form.submit()` ao
  confirmar). Os formulários usam `onsubmit="return abrirModal('...', this)"`.

## Inconsistências encontradas fora do escopo deste prompt
1. **Imagens de refeição fora da pasta esperada.** `aluno/home.php` e o novo
   preview apontam para `assets/img/refeicoes/<arquivo>`, mas essa pasta está
   **vazia** e o único arquivo de prato (`feijoada.webp`) está em `assets/img/`.
   Resultado: o hero e o preview cairão no fallback `prato-padrao.webp`
   ("Arquivo não encontrado"). É questão de organização de arquivos/conteúdo,
   não de código.
2. **`index.php` mantém `.grupo-campo` inline.** Como é página pré-login (sem
   navbar) e fora dos passos listados, não foi alterada; agora há uma definição
   duplicada (idêntica) à global. Pode ser centralizada futuramente.
3. **Escala "+25%" x `18px`.** O título do Passo 2 cita +25%, mas a instrução
   explícita é `font-size: 18px` (≈ +12,5% sobre 16px). Implementado o valor
   explícito (`18px`).
4. **Normalização visual ao centralizar.** A tabela/selos de `resumo.php` eram
   levemente menores (0.85rem/0.72rem) e a coluna do formulário de
   `alunos.php`/`turmas.php` era 320px; ambas foram unificadas ao padrão admin
   (`tabela-admin` 0.9rem/0.75rem; `admin-grid` 340px). Diferença mínima.
5. **`main.js` não é carregado em `index.php`/`alterar-senha.php`** (sem navbar):
   intencional — essas telas não têm drawer, dropdown nem modal.
