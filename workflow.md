# Workflow do Projeto — Sistema de Intenção Alimentar Escolar

## 1. Stack tecnológica

### 1.1 Backend
- **Linguagem:** PHP puro (sem frameworks, sem Composer)
- **Banco de dados:** MySQL
- **Administração do banco:** phpMyAdmin
- **Servidor de desenvolvimento:** XAMPP (Apache + MySQL local)
- **Servidor de produção:** servidor próprio da escola (Apache)

### 1.2 Frontend
- **Markup:** HTML5
- **Estilo:** CSS3 puro (sem frameworks)
- **Comportamento:** JavaScript puro (sem bibliotecas externas, exceto Chart.js para gráficos)

### 1.3 Integração externa
- **API de mensageria:** Evolution API (hospedada no Railway)
- **Protocolo de comunicação:** HTTP/HTTPS — o PHP realiza requisições à Evolution API via `cURL`
- **Canal de envio:** WhatsApp (pareamento por QR Code, sem conta Business)

### 1.4 Controle de versão
- **Ferramenta:** Git + GitHub (repositório privado)

---

## 2. Arquitetura do sistema

O sistema adota uma arquitetura **híbrida**:

- A maior parte das páginas é renderizada **server-side** pelo PHP (geração de HTML no servidor).
- Ações específicas — como registrar a intenção alimentar, atualizar dados em tempo real e acionar o envio via WhatsApp — utilizam **requisições AJAX** (fetch API do JavaScript) a endpoints PHP que retornam JSON.

Essa abordagem mantém a segurança do server-side para navegação e autenticação, e garante uma experiência de uso mais fluida nos pontos críticos do sistema.

### 2.1 Diagrama simplificado

```
[Navegador]
    |
    |-- Requisição de página (GET/POST) --> [PHP server-side] --> HTML renderizado
    |
    |-- Requisição AJAX (fetch + JSON) --> [/api/*.php] --> JSON
                                                |
                                          [MySQL via PDO]
                                                |
                                    (quando necessário)
                                                |
                                        [Evolution API
                                         no Railway]
                                         via cURL
```

---

## 3. Estrutura de pastas

```
/sistema-intencao-alimentar/
│
├── index.php                  # Página de login (ponto de entrada)
├── logout.php                 # Encerra a sessão e redireciona para login
│
├── /admin/                    # Páginas exclusivas do perfil Admin
│   ├── dashboard.php          # Dashboard com dados consolidados e gráfico
│   ├── alunos.php             # Listagem e cadastro de alunos
│   ├── turmas.php             # Listagem e cadastro de turmas
│   ├── refeicoes.php          # Listagem e cadastro de refeições
│   ├── enquete.php            # Visualização de resultados da enquete ativa
│   └── resumo.php             # Geração e envio do resumo à cozinha
│
├── /aluno/                    # Páginas exclusivas do perfil Aluno
│   ├── home.php               # Exibe a refeição do dia e formulário de resposta
│   └── alterar-senha.php      # Tela de criação de senha no primeiro acesso
│
├── /api/                      # Endpoints PHP que retornam JSON (consumidos via AJAX)
│   ├── responder.php          # Registra ou atualiza a intenção alimentar do aluno
│   ├── resultados.php         # Retorna os dados consolidados da enquete ativa
│   ├── enviar-resumo.php      # Aciona o envio do resumo via Evolution API (cURL)
│   └── grafico.php            # Retorna histórico de votos por data para o gráfico
│
├── /includes/                 # Arquivos reutilizáveis e utilitários
│   ├── db.php                 # Conexão com o banco de dados via PDO
│   ├── auth.php               # Funções de autenticação e verificação de sessão
│   ├── funcoes.php            # Funções auxiliares gerais
│   └── config.php             # Configurações globais (URL da Evolution API, etc.)
│
└── /assets/                   # Recursos estáticos
    ├── /css/
    │   └── style.css
    ├── /js/
    │   ├── main.js            # Scripts gerais
    │   └── grafico.js         # Inicialização e atualização do gráfico (Chart.js)
    └── /img/
```

---

## 4. Banco de dados

### 4.1 Convenções
- Nomes de tabelas: `snake_case`, no plural
- Nomes de colunas: `snake_case`
- Chaves primárias: `id` (INT, AUTO_INCREMENT)
- Chaves estrangeiras: `<entidade>_id`
- Datas e horários: `DATETIME` ou `TIME` conforme o campo
- Senhas: armazenadas com `password_hash()` (algoritmo `PASSWORD_DEFAULT`)

### 4.2 Tabelas previstas

| Tabela | Descrição |
|---|---|
| `usuarios` | Dados de autenticação e tipo de perfil |
| `alunos` | Dados escolares vinculados a um usuário |
| `turmas` | Turmas organizadas por curso, período e ano letivo |
| `refeicoes` | Refeições cadastradas com data e horário limite |
| `intencoes_alimentares` | Respostas dos alunos por refeição |
| `resumos_envio` | Histórico de resumos gerados e enviados à cozinha |

### 4.3 Constraints importantes
- `usuarios.rm` — UNIQUE (quando aplicável ao perfil aluno)
- `intencoes_alimentares (aluno_id, refeicao_id)` — UNIQUE KEY composta (impede duplicidade de resposta)
- `alunos.usuario_id` — FOREIGN KEY para `usuarios.id`
- `alunos.turma_id` — FOREIGN KEY para `turmas.id`

---

## 5. Autenticação e controle de sessão

### 5.1 Mecanismo
- Login via `index.php`: recebe RM e senha via POST
- PHP valida credenciais contra o banco com `password_verify()`
- Em caso de sucesso, inicia sessão com `session_start()` e armazena:
  - `$_SESSION['usuario_id']`
  - `$_SESSION['tipo']` — `'aluno'` ou `'admin'`
  - `$_SESSION['senha_alterada']` — `true` ou `false`

### 5.2 Proteção de páginas
- Todas as páginas em `/admin/` incluem `auth.php` no topo
- `auth.php` verifica:
  1. Se a sessão está ativa — caso contrário, redireciona para `index.php`
  2. Se o tipo de usuário tem permissão para aquela área
  3. Se é o primeiro acesso do aluno (`senha_alterada = false`) — caso seja, redireciona para `alterar-senha.php`

### 5.3 Proteção dos endpoints `/api/`
- Todo arquivo em `/api/` inicia com verificação de sessão antes de qualquer lógica
- Requisições sem sessão válida retornam HTTP 401 com JSON `{"erro": "não autorizado"}`
- Nenhum endpoint em `/api/` é acessível diretamente pelo navegador sem sessão ativa

---

## 6. Integração com Evolution API

### 6.1 Fluxo de envio
1. Admin acessa `resumo.php` e clica em "Enviar para a cozinha"
2. O JavaScript realiza um `fetch` para `/api/enviar-resumo.php`
3. O PHP monta a mensagem com os dados da enquete
4. O PHP realiza uma requisição `cURL` POST para a Evolution API hospedada no Railway
5. A Evolution API encaminha a mensagem ao número ou grupo configurado no WhatsApp
6. O PHP registra o envio na tabela `resumos_envio` (status, horário, Admin responsável)
7. O endpoint retorna JSON com status de sucesso ou erro para o frontend

### 6.2 Configuração
- URL da Evolution API e token de autenticação armazenados em `includes/config.php`
- `config.php` não deve ser versionado no GitHub (adicionar ao `.gitignore`)

### 6.3 Dependência crítica
- O servidor da escola precisa conseguir realizar requisições HTTP externas para o Railway
- Esse ponto deve ser confirmado com o responsável pelo servidor antes do desenvolvimento da integração

---

## 7. Checamento de enquete encerrada

Não há cron job. O sistema verifica o status da enquete de forma reativa a cada requisição:

```php
// Exemplo de verificação em api/responder.php
$agora = new DateTime();
$limite = new DateTime($refeicao['horario_limite']);

if ($agora > $limite) {
    http_response_code(403);
    echo json_encode(['erro' => 'Enquete encerrada.']);
    exit;
}
```

Essa lógica é replicada em todo ponto onde o sistema precisa bloquear ações após o horário limite.

---

## 8. Gráfico histórico (Chart.js)

- O gráfico na dashboard do Admin consome o endpoint `/api/grafico.php`
- O endpoint retorna JSON com votos agrupados por data:

```json
[
  { "data": "2025-06-01", "sim": 87, "nao": 12, "sem_resposta": 15 },
  { "data": "2025-06-02", "sim": 91, "nao": 8, "sem_resposta": 10 }
]
```

- `grafico.js` inicializa um gráfico de barras ou linhas com Chart.js usando esses dados
- Chart.js é carregado via CDN (`cdnjs.cloudflare.com`) — sem instalação local necessária

---

## 9. Ambiente de desenvolvimento

### 9.1 Local
- XAMPP instalado na máquina de desenvolvimento
- Projeto em `/htdocs/sistema-intencao-alimentar/`
- Banco criado e gerenciado via phpMyAdmin local
- Variáveis de ambiente (URL do Railway, token) definidas em `includes/config.php`

### 9.2 Produção (servidor da escola)
- Projeto enviado via FTP ou Git pull direto no servidor
- Banco de dados criado no MySQL do servidor da escola
- `config.php` configurado com as credenciais e URL de produção
- Confirmar com o responsável:
  - Versão do PHP disponível (recomendado PHP 8.0+)
  - Se o Apache tem `mod_rewrite` habilitado (para URLs limpas, se necessário)
  - Se o servidor consegue fazer requisições HTTP externas (necessário para a Evolution API)

---

## 10. Controle de versão (Git)

### 10.1 Arquivos a ignorar (.gitignore)
```
includes/config.php
/vendor/
*.log
.DS_Store
Thumbs.db
```

### 10.2 Branches sugeridas
- `main` — código estável, reflete o que está em produção
- `dev` — desenvolvimento ativo
- `feature/<nome>` — funcionalidades isoladas (ex: `feature/envio-whatsapp`)

---

## 11. Ordem de desenvolvimento sugerida

A ordem abaixo prioriza a base estrutural antes das funcionalidades, evitando retrabalho.

### Fase 1 — Fundação
1. Criação do banco de dados e todas as tabelas no MySQL
2. `includes/db.php` — conexão PDO
3. `includes/config.php` — configurações globais
4. `includes/auth.php` — funções de sessão e verificação
5. `index.php` — tela de login funcional
6. `logout.php`

### Fase 2 — Área do Admin
7. `admin/dashboard.php` — estrutura inicial (sem gráfico ainda)
8. `admin/turmas.php` — CRUD completo de turmas
9. `admin/alunos.php` — CRUD completo de alunos (com vínculo a turmas)
10. `admin/refeicoes.php` — cadastro de refeições com horário limite

### Fase 3 — Área do Aluno
11. `aluno/alterar-senha.php` — tela de primeiro acesso
12. `aluno/home.php` — exibição da refeição do dia
13. `api/responder.php` — endpoint de intenção alimentar (AJAX)

### Fase 4 — Resultados e Dashboard
14. `api/resultados.php` — dados consolidados da enquete
15. `admin/enquete.php` — visualização em tempo real
16. `api/grafico.php` — histórico por data
17. `assets/js/grafico.js` + integração Chart.js na dashboard

### Fase 5 — Envio via WhatsApp
18. `admin/resumo.php` — geração do resumo
19. `api/enviar-resumo.php` — integração cURL com Evolution API
20. Registro na tabela `resumos_envio`

### Fase 6 — Revisão e entrega
21. Testes gerais de fluxo (aluno e admin)
22. Validações de segurança (sessão, SQL injection via PDO, primeiro acesso)
23. Ajustes de CSS e responsividade
24. Deploy no servidor da escola
25. Documentação final do TCC
