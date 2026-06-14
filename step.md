# Guia de Execução Local — Sistema de Intenção Alimentar Escolar

Este documento instrui os membros do projeto a configurar e executar o sistema no ambiente local (localhost). Leia com atenção antes de começar.

---

## 1. Visão geral do projeto

**Nome:** Sistema de Intenção Alimentar Escolar
**Objetivo:** Permitir que alunos registrem se pretendem consumir a refeição escolar do dia, auxiliando a escola a reduzir o desperdício alimentar.
**Tipo:** Aplicação web — TCC do curso técnico.

### Stack utilizada

| Camada | Tecnologia |
|---|---|
| Backend | PHP puro (sem frameworks) |
| Banco de dados | MySQL |
| Frontend | HTML5 + CSS3 + JavaScript puro |
| Gráficos | Chart.js (via CDN) |
| Servidor local | XAMPP |
| Controle de versão | Git + GitHub |
| Integração WhatsApp | Evolution API (hospedada no Railway) |

---

## 2. Softwares necessários

Instale os seguintes softwares antes de prosseguir.

### 2.1 XAMPP
O XAMPP fornece o servidor Apache (para rodar PHP) e o MySQL (banco de dados) na sua máquina.

- Download: https://www.apachefriends.org
- Versão recomendada: qualquer versão com **PHP 8.0 ou superior**
- Durante a instalação, mantenha marcados pelo menos: **Apache**, **MySQL** e **phpMyAdmin**

### 2.2 Git
Necessário para clonar o repositório e contribuir com o projeto.

- Download: https://git-scm.com
- Após instalar, verifique no terminal:
```bash
git --version
```

### 2.3 Editor de código
Recomendado: **Visual Studio Code**
- Download: https://code.visualstudio.com
- Extensões recomendadas:
  - PHP Intelephense
  - GitLens

---

## 3. Estrutura de pastas do projeto

Familiarize-se com a organização antes de mexer em qualquer arquivo.

```
/sistema-intencao-alimentar/
│
├── index.php                  # Ponto de entrada — tela de login
├── logout.php                 # Encerra sessão e redireciona ao login
│
├── /admin/                    # Páginas acessíveis apenas pelo perfil Admin
│   ├── dashboard.php          # Dashboard com dados e gráfico histórico
│   ├── alunos.php             # Cadastro e listagem de alunos
│   ├── turmas.php             # Cadastro e listagem de turmas
│   ├── refeicoes.php          # Cadastro de refeições e horário limite
│   ├── enquete.php            # Resultados em tempo real da enquete ativa
│   └── resumo.php             # Geração e envio do resumo à cozinha
│
├── /aluno/                    # Páginas acessíveis apenas pelo perfil Aluno
│   ├── home.php               # Exibe a refeição do dia e coleta a resposta
│   └── alterar-senha.php      # Obrigatória no primeiro acesso
│
├── /api/                      # Endpoints PHP que retornam JSON (uso interno via AJAX)
│   ├── responder.php          # Registra ou atualiza a intenção alimentar
│   ├── resultados.php         # Dados consolidados da enquete ativa
│   ├── enviar-resumo.php      # Dispara o envio via Evolution API
│   └── grafico.php            # Histórico de votos por data para o gráfico
│
├── /includes/                 # Utilitários e arquivos reutilizáveis
│   ├── db.php                 # Conexão com o banco via PDO
│   ├── auth.php               # Verificação de sessão e permissões
│   ├── funcoes.php            # Funções auxiliares gerais
│   └── config.php             # ⚠️ NÃO versionar — contém credenciais e URL da API
│
└── /assets/                   # Recursos estáticos
    ├── /css/
    │   └── style.css
    ├── /js/
    │   ├── main.js
    │   └── grafico.js
    └── /img/
```

> ⚠️ **Atenção:** o arquivo `includes/config.php` contém credenciais sensíveis e **não está no repositório**. Você precisará criá-lo manualmente seguindo o passo 6 deste guia.

---

## 4. Clonando o repositório

1. Abra o terminal (ou Git Bash no Windows)
2. Navegue até a pasta `htdocs` do XAMPP:

```bash
# Windows (padrão)
cd C:/xampp/htdocs

# macOS (padrão)
cd /Applications/XAMPP/htdocs
```

3. Clone o repositório:

```bash
git clone https://github.com/<usuario>/<repositorio>.git
```

4. Entre na pasta do projeto:

```bash
cd sistema-intencao-alimentar
```

---

## 5. Configurando o banco de dados

### 5.1 Iniciando o XAMPP
1. Abra o **XAMPP Control Panel**
2. Clique em **Start** nas linhas **Apache** e **MySQL**
3. Aguarde até ambos ficarem com o status verde

### 5.2 Criando o banco no phpMyAdmin
1. Acesse no navegador: `http://localhost/phpmyadmin`
2. Clique em **Novo** (painel esquerdo)
3. Nomeie o banco como: `sistema_alimentar`
4. Selecione o cotejamento: `utf8mb4_unicode_ci`
5. Clique em **Criar**

### 5.3 Importando o schema
1. Com o banco `sistema_alimentar` selecionado, clique na aba **Importar**
2. Clique em **Escolher arquivo** e selecione o arquivo `database/schema.sql` do projeto
3. Clique em **Executar**
4. Verifique se todas as tabelas foram criadas sem erros

As tabelas criadas serão:

| Tabela | Descrição |
|---|---|
| `usuarios` | Dados de autenticação e tipo de perfil |
| `alunos` | Dados escolares vinculados a um usuário |
| `turmas` | Turmas organizadas por curso, período e ano letivo |
| `refeicoes` | Refeições cadastradas com data e horário limite |
| `intencoes_alimentares` | Respostas dos alunos por refeição |
| `resumos_envio` | Histórico de resumos gerados e enviados à cozinha |

---

## 6. Criando o arquivo config.php

O arquivo `includes/config.php` não está no repositório por segurança. Crie-o manualmente:

1. Na pasta `includes/`, crie um novo arquivo chamado `config.php`
2. Cole o seguinte conteúdo e preencha com os dados corretos:

```php
<?php

// Fuso horário padrão — alinha date() do PHP com os horários do MySQL
date_default_timezone_set('America/Sao_Paulo');

// Banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_alimentar');
define('DB_USER', 'root');       // usuário padrão do XAMPP
define('DB_PASS', '');           // senha padrão do XAMPP é vazia

// Evolution API (WhatsApp)
define('EVOLUTION_API_URL', 'https://<sua-instancia>.railway.app');
define('EVOLUTION_API_TOKEN', '<seu-token-aqui>');
define('EVOLUTION_INSTANCIA', '<nome-da-instancia>');
define('WHATSAPP_DESTINO', '<numero-ou-grupo>'); // ex: 5511999999999
```

> 💡 Os valores da Evolution API serão fornecidos pelo membro responsável pela configuração do Railway. Se ainda não tiver, deixe as constantes da API com valores vazios — o restante do sistema funcionará normalmente no localhost.

---

## 7. Acessando o sistema

Com o XAMPP rodando e o banco configurado, acesse no navegador:

```
http://localhost/sistema-intencao-alimentar/
```

Você verá a tela de login. Para o primeiro acesso, utilize o usuário Admin padrão inserido pelo schema:

| Campo | Valor |
|---|---|
| RM | `admin` |
| Senha | `admin123` |

> ⚠️ Altere a senha do Admin após o primeiro acesso em ambiente de desenvolvimento compartilhado.

---

## 8. Fluxo básico para testar localmente

Siga essa sequência para validar se tudo está funcionando:

1. **Login como Admin** → acesse com as credenciais acima
2. **Cadastre uma turma** → `Admin > Turmas > Nova turma`
3. **Cadastre um aluno** → `Admin > Alunos > Novo aluno` (senha padrão = RM do aluno)
4. **Cadastre uma refeição** → `Admin > Refeições > Nova refeição` (defina um horário limite futuro)
5. **Login como Aluno** → abra uma aba anônima e acesse com o RM e senha do aluno cadastrado
6. **Primeiro acesso** → o sistema deve redirecionar para a tela de criação de senha
7. **Registre a intenção** → responda à refeição cadastrada
8. **Volte ao Admin** → verifique se a resposta aparece nos resultados da enquete

---

## 9. Problemas comuns

**Apache ou MySQL não iniciam no XAMPP**
- Verifique se a porta 80 (Apache) ou 3306 (MySQL) não está sendo usada por outro programa (Skype, IIS, etc.)
- Tente alterar a porta do Apache para 8080 nas configurações do XAMPP e acesse `http://localhost:8080/...`

**Erro de conexão com o banco**
- Confirme que o MySQL está rodando no XAMPP
- Verifique os valores de `DB_USER` e `DB_PASS` no `config.php`
- Confirme que o banco `sistema_alimentar` foi criado corretamente

**Página em branco ou erro 500**
- Ative a exibição de erros do PHP temporariamente adicionando no topo do arquivo com problema:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```
- Remova essas linhas após identificar o problema

**Redirecionamento de sessão não funciona**
- Verifique se `session_start()` está sendo chamado antes de qualquer saída HTML
- Certifique-se de que `auth.php` está incluído no topo da página

---

## 10. Boas práticas para o time

- **Nunca commite o `config.php`** — ele já está no `.gitignore`, mas fique atento
- **Sempre rode `git pull`** antes de começar a trabalhar para evitar conflitos
- **Nomeie seus commits de forma descritiva** — ex: `feat: cadastro de alunos`, `fix: validação de RM duplicado`
- **Não edite diretamente a branch `main`** — trabalhe em `dev` ou em uma branch de feature
- **Teste o fluxo completo** (login aluno + login admin) antes de abrir um Pull Request
