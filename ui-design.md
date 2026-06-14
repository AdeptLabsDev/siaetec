# UI Design — Sistema de Intenção Alimentar Escolar (SIAetec)
## Páginas do Aluno

Este documento define a identidade visual, estrutura de componentes e comportamento das interfaces do perfil Aluno. Serve como referência para o desenvolvimento do frontend e para orientar o agente no VS Code.

---

## 1. Identidade visual

### 1.1 Paleta de cores

Todas as cores devem ser declaradas como variáveis CSS no seletor `:root`, escritas em português.

```css
:root {
    --branco: #FFFFFF;
    --preto: #000000;

    /* Vermelhos */
    --vermelho-institucional: #941612;
    --vermelho-atrativo: #EB0033;
    --vermelho-hover: #B30027;

    /* Neutros */
    --cinza-claro: #F5F5F5;
    --cinza-medio: #D1D1D1;
    --cinza-texto: #4A555C;
    --cinza-borda: #E0E0E0;
}
```

### 1.2 Tipografia

#### Carregamento otimizado de fontes (Google Fonts)

As fontes são carregadas via Google Fonts com técnicas de otimização de performance. Adicionar no `<head>` de **todos os arquivos PHP** na seguinte ordem:

```html
<!-- 1. Preconnect: abre conexão antecipada com os servidores do Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<!-- 2. Carrega apenas os pesos necessários (400, 500, 600, 700) -->
<!-- display=swap evita texto invisível enquanto a fonte carrega (FOIT) -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
```

**Por que esse formato é mais leve:**
- `preconnect` reduz a latência ao abrir a conexão com `fonts.googleapis.com` e `fonts.gstatic.com` antes do browser precisar deles
- `display=swap` garante que o texto apareça imediatamente com a fonte de fallback, sem travar a renderização
- Solicitar apenas os pesos `400;500;600;700` reduz significativamente o tamanho do arquivo comparado a carregar todos os pesos
- O Google Fonts serve automaticamente o formato `.woff2` para browsers modernos — o formato mais comprimido disponível (suporte de 97%+ dos browsers)

**Fallback no CSS:**

```css
:root {
    --fonte-principal: 'Inter', Verdana, Arial, sans-serif;
}

body {
    font-family: var(--fonte-principal);
}
```

Escala tipográfica:

| Uso | Tamanho | Peso |
|---|---|---|
| Título principal (h1) | 2rem | 700 |
| Subtítulo (h2) | 1.5rem | 600 |
| Título de seção (h3) | 1.25rem | 600 |
| Texto corrido (p) | 1rem | 400 |
| Texto secundário | 0.875rem | 400 |
| Label / rótulo | 0.75rem | 500 |

### 1.3 Bordas e sombras

```css
:root {
    --raio-borda-pequeno: 6px;
    --raio-borda-medio: 12px;
    --raio-borda-grande: 20px;
    --sombra-suave: 0 2px 8px rgba(0, 0, 0, 0.08);
    --sombra-cartao: 0 4px 16px rgba(0, 0, 0, 0.10);
}
```

### 1.4 Otimizações de performance

Aplicar em todo o projeto, independente da tela.

#### Imagens
- Todas as imagens devem estar no formato `.webp` — menor tamanho com igual ou superior qualidade visual
- Usar o atributo `loading="lazy"` em todas as imagens **abaixo da dobra** (fora da área visível inicial):
```html
<img src="imagem.webp" alt="Descrição" loading="lazy" width="600" height="320">
```
- A imagem principal do hero (prato do dia) **não deve ter** `loading="lazy"` — ela é crítica para o LCP (Largest Contentful Paint) e deve carregar imediatamente
- Sempre declarar `width` e `height` nas tags `<img>` para evitar layout shift (CLS)
- Usar `decoding="async"` em imagens secundárias para não bloquear o thread principal:
```html
<img src="imagem.webp" alt="Descrição" loading="lazy" decoding="async" width="600" height="320">
```

#### CSS e JavaScript
- O arquivo `style.css` deve ser carregado no `<head>` (bloqueia renderização intencionalmente — é o CSS crítico)
- Scripts JS devem ser carregados no final do `<body>` ou com o atributo `defer`:
```html
<script src="assets/js/main.js" defer></script>
```
- O Chart.js (CDN) também deve usar `defer`:
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js" defer></script>
```

#### Meta tags essenciais
Incluir no `<head>` de todos os arquivos PHP:
```html
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow"> <!-- sistema interno, não indexar -->
```

#### Compressão GZIP no Apache
Adicionar no `.htaccess` da raiz do projeto para comprimir HTML, CSS e JS antes de enviar ao browser:
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css application/javascript
</IfModule>
```

---

## 2. Componentes globais

### 2.1 Navbar

Presente em todas as páginas do aluno após o login. Fixa no topo (`position: sticky; top: 0`).

**Estrutura:**
```
[ Logo SIAetec ] [ Logo Etec + CPS ]     [ Links de navegação ]  [ Ícone de perfil ]
```

**Logos:**
- `logo-siaetec.webp` — exibida à esquerda
- `logo-etec-cps.webp` — ao lado da logo SIAetec, separada por um divisor vertical sutil
- Altura máxima das logos: `40px`

**Links de navegação (desktop):**
- Início · Enquete · Sugestões · Suporte · Sobre
- Link ativo com sublinhado e cor `--vermelho-institucional`
- Hover com transição suave de cor

**Ícone de perfil:**
- Ícone circular à direita (avatar genérico SVG)
- Ao clicar, abre um dropdown com: Nome do aluno, RM, link para Perfil, botão Sair
- Dropdown com `border-radius: var(--raio-borda-medio)` e `box-shadow: var(--sombra-cartao)`

**Comportamento mobile (abaixo de 768px):**
- Links de navegação são ocultados
- Substituídos por um botão de menu hambúrguer (ícone ≡) à direita, antes do ícone de perfil
- Ao clicar no hambúrguer, exibe um menu lateral deslizante (drawer) vindo da direita
- O drawer cobre a tela com um overlay escuro semitransparente
- Dentro do drawer: links empilhados verticalmente com espaçamento generoso (mínimo 48px de altura por item para toque confortável)
- Botão de fechar (×) no topo do drawer

**CSS base da navbar:**
```css
.barra-navegacao {
    background-color: var(--branco);
    border-bottom: 3px solid var(--vermelho-institucional);
    padding: 0 1.5rem;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: var(--sombra-suave);
}
```

---

### 2.2 Botões

**Botão primário (ação principal):**
```css
.botao-primario {
    background-color: var(--vermelho-atrativo);
    color: var(--branco);
    border: none;
    border-radius: var(--raio-borda-pequeno);
    padding: 0.75rem 1.5rem;
    font-family: var(--fonte-principal);
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.2s ease;
    width: 100%;
}

.botao-primario:hover {
    background-color: var(--vermelho-hover);
}
```

**Botão secundário (ação neutra):**
```css
.botao-secundario {
    background-color: transparent;
    color: var(--vermelho-institucional);
    border: 2px solid var(--vermelho-institucional);
    border-radius: var(--raio-borda-pequeno);
    padding: 0.75rem 1.5rem;
    font-family: var(--fonte-principal);
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
}

.botao-secundario:hover {
    background-color: var(--vermelho-institucional);
    color: var(--branco);
}
```

---

### 2.3 Cartão (card)

Componente reutilizável para agrupar conteúdo em seções.

```css
.cartao {
    background-color: var(--branco);
    border-radius: var(--raio-borda-medio);
    box-shadow: var(--sombra-cartao);
    padding: 1.5rem;
    width: 100%;
}
```

---

## 3. Tela de Login (`index.php`)

### Layout
- Tela centralizada verticalmente e horizontalmente
- Fundo: `--cinza-claro`
- Cartão de login centralizado com largura máxima de `420px`
- Logo SIAetec no topo do cartão

### Abas
Duas abas: **Aluno** e **Admin**
- Aba ativa: borda inferior `--vermelho-atrativo`, texto `--vermelho-institucional`, peso 600
- Aba inativa: texto `--cinza-texto`
- Transição suave ao trocar de aba

### Campos por aba

**Aba Aluno:**
- Campo: RM (texto, obrigatório)
- Campo: Senha (password, obrigatório)
- Botão primário: "Entrar"

**Aba Admin:**
- Campo: Usuário (texto, obrigatório)
- Campo: Senha (password, obrigatório)
- Botão primário: "Entrar"

### Comportamento
- Mensagem de erro exibida abaixo do botão em caso de credenciais inválidas
- Cor da mensagem de erro: `--vermelho-atrativo`
- Após login bem-sucedido: redireciona para `/aluno/home.php`
- Se for primeiro acesso do aluno: redireciona para `/aluno/alterar-senha.php`

---

## 4. Tela de Primeiro Acesso — Alterar Senha (`aluno/alterar-senha.php`)

### Layout
- Mesma estrutura centralizada da tela de login
- Cartão único, sem abas
- Título: "Crie sua senha"
- Subtítulo explicativo: "Este é seu primeiro acesso. Defina uma senha pessoal para continuar."

### Campos
- Nova senha (password, obrigatório)
- Confirmar nova senha (password, obrigatório)
- Botão primário: "Confirmar"

### Comportamento
- Validação client-side: senhas devem coincidir antes do envio
- Indicador visual de força da senha (fraca / média / forte) abaixo do campo
- Erro inline caso as senhas não coincidam
- Após confirmação: redireciona para `/aluno/home.php`

---

## 5. Início (`aluno/home.php`)

### Layout
- Página com navbar + hero centralizado
- Fundo da página: `--cinza-claro`

### Hero — Cartão do Prato do Dia
Cartão dividido em duas colunas no desktop, empilhado no mobile:

**Coluna esquerda (conteúdo):**
- Rótulo: "PRATO DO DIA · DD/MM" — maiúsculas, `--vermelho-institucional`, peso 700
- Título: nome da refeição em destaque (h1)
- Descrição: ingredientes/cardápio em texto corrido
- Texto de chamada: "Registre sua intenção alimentar..."
- Botão primário: "ENQUETE →"
- Contador regressivo abaixo do botão: "Enquete encerra em HH:MM:SS" — texto pequeno, `--cinza-texto`

**Coluna direita (imagem):**
- Imagem da refeição com `border-radius: var(--raio-borda-grande)`
- `object-fit: cover`, altura fixa de `320px` no desktop
- No mobile: imagem aparece acima do conteúdo, altura `220px`

**Comportamento:**
- Se não houver refeição cadastrada para o dia: exibe mensagem neutra "Nenhuma refeição disponível no momento"
- Se a enquete estiver encerrada: botão desabilitado com texto "Enquete encerrada", cor `--cinza-medio`
- O contador regressivo é atualizado via JavaScript a cada segundo (sem AJAX — apenas cálculo client-side com o horário limite vindo do PHP)

---

## 6. Enquete (`aluno/enquete.php` ou seção modal)

> A enquete pode ser exibida como página separada ou como modal acionado pelo botão do hero. Decisão a ser tomada durante o desenvolvimento.

### Layout
- Cartão centralizado, largura máxima `480px`
- Título: "Você vai almoçar hoje?" (ou equivalente)
- Subtítulo: nome e data da refeição

### Opções de resposta
Dois botões grandes e bem espaçados — otimizados para toque no mobile:

```
[ ✓  SIM, vou almoçar   ]
[ ✗  NÃO vou almoçar    ]
```

- Altura mínima de cada botão: `64px`
- Botão "Sim": fundo `--vermelho-atrativo`, texto branco
- Botão "Não": estilo secundário (borda `--vermelho-institucional`)
- Após responder: exibe confirmação com a resposta registrada e opção de alterar (enquanto a enquete estiver aberta)
- Se a enquete estiver encerrada: exibe apenas a resposta registrada, sem opção de alterar

---

## 7. Sugestões (`aluno/sugestoes.php`)

### Layout
- Página com navbar
- Título da seção: "Envie sua sugestão"
- Subtítulo explicativo

### Formulário
- Campo: Assunto (select com opções: Cardápio, Atendimento, Sistema, Outro)
- Campo: Mensagem (textarea, mínimo 4 linhas, máximo 500 caracteres)
- Contador de caracteres abaixo do textarea
- Botão primário: "Enviar Sugestão"

### Comportamento
- Envio via POST (server-side)
- Após envio: exibe mensagem de sucesso inline, sem recarregar a página (AJAX)
- Mensagem de sucesso: "Sugestão enviada com sucesso!" em verde (`#2E7D32`)

---

## 8. Suporte (`aluno/suporte.php`)

### Layout
- Página com navbar
- Conteúdo estático — sem formulário
- Título: "Suporte"

### Conteúdo
- Texto introdutório explicando como obter ajuda
- Cartões de contato com ícone + informação:
  - E-mail de contato da escola
  - Horário de atendimento
  - Responsável pelo sistema (nome do grupo/TCC)

### Estética
- Cartões lado a lado no desktop, empilhados no mobile
- Ícones SVG inline, cor `--vermelho-institucional`

---

## 9. Sobre (`aluno/sobre.php`)

### Layout
- Página com navbar
- Conteúdo estático e institucional

### Conteúdo
- Título: "Sobre o SIAetec"
- Parágrafo explicando o objetivo do sistema
- Seção "Desenvolvido por" — nomes dos integrantes do grupo com papel (ex: Backend, Frontend)
- Logos da Etec e do CPS centralizadas no rodapé da seção

---

## 10. Perfil (`aluno/perfil.php`)

### Layout
- Página com navbar
- Cartão centralizado, largura máxima `420px`
- Avatar genérico no topo (ícone SVG circular, fundo `--cinza-claro`)

### Informações exibidas (somente leitura)
- Nome completo
- RM
- Turma
- Série / Ano letivo

### Ações
- Botão secundário: "Alterar Senha" → redireciona para `alterar-senha.php`
- Botão de saída: "Sair" → chama `logout.php`, com cor `--cinza-texto` e ícone de saída

---

## 11. Responsividade — diretrizes gerais

| Breakpoint | Largura | Comportamento |
|---|---|---|
| Mobile | até 767px | Layout em coluna única, navbar com hambúrguer, botões full-width |
| Tablet | 768px – 1023px | Layout em coluna única ou duas colunas conforme o conteúdo |
| Desktop | 1024px ou mais | Layout completo conforme descrito em cada tela |

**Regras gerais para mobile:**
- Área mínima de toque: `48px × 48px` para qualquer elemento interativo
- Padding lateral mínimo de `1rem` em todas as páginas
- Fontes nunca abaixo de `14px` em mobile
- Imagens com `max-width: 100%` e `height: auto`
- Inputs e textareas com `font-size: 16px` mínimo (evita zoom automático no iOS)

---

## 12. Observações para o agente

**CSS e identidade visual:**
- Todas as variáveis CSS devem estar em português, declaradas no `:root` de `style.css`
- Nenhuma biblioteca CSS externa (sem Bootstrap, sem Tailwind)
- Usar apenas as cores definidas nas variáveis — nunca valores hexadecimais avulsos no CSS

**JavaScript:**
- Chart.js é a única dependência JS externa permitida (carregada via CDN com `defer`)
- Todos os scripts devem ser carregados no final do `<body>` ou com `defer`
- Ícones devem ser SVG inline ou via tag `<img>` local — sem Font Awesome ou similar

**Performance:**
- Imagens sempre em `.webp`
- `loading="lazy"` em todas as imagens exceto a imagem principal do hero
- `width` e `height` declarados em todas as tags `<img>`
- `decoding="async"` em imagens secundárias
- `<link rel="preconnect">` para Google Fonts antes do link da fonte

**Acessibilidade e semântica:**
- Imagens devem ter `alt` descritivo em português
- Todos os formulários devem ter `<label>` associado a cada `<input>` (atributo `for` + `id`)
- Usar tags semânticas HTML5: `<header>`, `<nav>`, `<main>`, `<section>`, `<footer>`

**PHP:**
- A navbar deve ser um componente PHP reutilizável incluído via `include`
- `auth.php` deve ser incluído no topo de toda página protegida, antes de qualquer HTML