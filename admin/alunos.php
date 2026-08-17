<?php
/**
 * admin/alunos.php
 * Cadastro e listagem de alunos.
 *
 * Cada aluno gera um registro em `usuarios` (login = RM, senha = provisória
 * ALEATÓRIA, senha_alterada = 0) e outro em `alunos`. A senha em texto puro é
 * exibida UMA ÚNICA vez logo após o cadastro e nunca é persistida — o CSV é o
 * canal PRINCIPAL de entrega das credenciais nesta fase; o e-mail é camada
 * adicional apenas quando o provedor estiver configurado.
 * Alunos não são deletados — apenas inativados/reativados.
 */

require_once __DIR__ . '/../includes/auth.php';  // sessão (antes de qualquer HTML)
require_once __DIR__ . '/../includes/db.php';    // $pdo
require_once __DIR__ . '/../includes/email.php'; // enviar_credenciais()

verificar_sessao('admin');
$usuario = usuario_logado();

// O provedor de e-mail pode não estar configurado nesta fase — estado ESPERADO.
// Distinguimos "não configurado" de "falha no envio" para nunca exibir o
// primeiro como erro. enviar_credenciais() retorna false nos dois casos.
$email_configurado = defined('BREVO_API_KEY') && BREVO_API_KEY !== ''
    && defined('BREVO_REMETENTE_EMAIL') && BREVO_REMETENTE_EMAIL !== ''
    && defined('BREVO_REMETENTE_NOME') && BREVO_REMETENTE_NOME !== '';

$erros       = [];
$valores     = ['nome' => '', 'rm' => '', 'email' => '', 'turma_id' => '', 'ano_letivo' => (int) date('Y')];
$credenciais = null; // preenchido só no request de cadastro bem-sucedido (senha exibida uma vez)

// --- Processamento de ações ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'cadastrar';

    // Alternar status usa PRG (não há dado sensível a exibir)
    if ($acao === 'alternar_status') {
        $aluno_id = filter_var($_POST['aluno_id'] ?? null, FILTER_VALIDATE_INT);
        if ($aluno_id) {
            try {
                $pdo->beginTransaction();
                // Alterna o status escolar e o de acesso (login) em conjunto
                $pdo->prepare('UPDATE alunos SET ativo = 1 - ativo WHERE id = :id')
                    ->execute([':id' => $aluno_id]);
                $pdo->prepare(
                    'UPDATE usuarios u
                       JOIN alunos a ON a.usuario_id = u.id
                        SET u.ativo = a.ativo
                      WHERE a.id = :id'
                )->execute([':id' => $aluno_id]);
                $pdo->commit();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            }
        }
        redirecionar('alunos.php?msg=status');
    }

    // Regeneração de credenciais (individual ou em lote). Responde com um CSV
    // como download direto — a página NÃO é recarregada; o CSV é o comprovante.
    if ($acao === 'regenerar') {
        // 1. Sessão de admin já garantida por verificar_sessao('admin') acima.
        $senha_admin = (string) ($_POST['senha_admin'] ?? '');
        $ids_brutos  = $_POST['ids'] ?? [];
        if (!is_array($ids_brutos)) {
            $ids_brutos = [];
        }

        // 2. Hash do admin buscado pelo id da SESSÃO — nunca por id do cliente.
        $admin_id = (int) ($_SESSION['usuario_id'] ?? 0);
        $stmt = $pdo->prepare(
            'SELECT senha FROM usuarios WHERE id = :id AND tipo = :tipo AND ativo = 1'
        );
        $stmt->execute([':id' => $admin_id, ':tipo' => 'admin']);
        $hash_admin = $stmt->fetchColumn();

        // 3. Reautenticação. Erro genérico — não revela nenhum outro detalhe.
        if ($hash_admin === false || !password_verify($senha_admin, (string) $hash_admin)) {
            redirecionar('alunos.php?msg=senha_incorreta');
        }

        // 4. Normaliza os ids e mantém só alunos existentes E ativos.
        $ids = [];
        foreach ($ids_brutos as $bruto) {
            $iv = filter_var($bruto, FILTER_VALIDATE_INT);
            if ($iv) {
                $ids[$iv] = $iv; // chave = valor deduplica
            }
        }
        $ids = array_values($ids);
        if (!$ids) {
            redirecionar('alunos.php?msg=regenerar_vazio');
        }

        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $sel = $pdo->prepare(
            "SELECT a.id AS aluno_id, u.id AS usuario_id, u.nome, u.email, a.rm
               FROM alunos a
               JOIN usuarios u ON u.id = a.usuario_id
              WHERE a.ativo = 1 AND u.ativo = 1 AND a.id IN ($marcadores)
              ORDER BY u.nome ASC"
        );
        $sel->execute($ids);
        $alvos = $sel->fetchAll();
        if (!$alvos) {
            redirecionar('alunos.php?msg=regenerar_invalido');
        }

        // 5. Regenera por aluno. A senha em texto puro vive só neste request e
        //    entra apenas no CSV transmitido — nunca em banco, session ou arquivo.
        $upd_senha = $pdo->prepare(
            'UPDATE usuarios SET senha = :senha, senha_alterada = 0 WHERE id = :id'
        );
        $upd_cred = $pdo->prepare(
            'UPDATE usuarios SET credenciais_enviadas = :env, credenciais_enviadas_em = :em WHERE id = :id'
        );

        $linhas = [];
        foreach ($alvos as $alvo) {
            $nova_senha = gerar_senha_aleatoria();

            // b. Só o hash vai ao banco; senha_alterada = 0 força a troca no 1º acesso
            $upd_senha->execute([
                ':senha' => password_hash($nova_senha, PASSWORD_DEFAULT),
                ':id'    => (int) $alvo['usuario_id'],
            ]);

            // c. Reenvio best-effort (só se o provedor estiver configurado)
            if ($email_configurado) {
                $enviado = enviar_credenciais(
                    (string) $alvo['email'],
                    (string) $alvo['nome'],
                    (string) $alvo['rm'],
                    $nova_senha
                );
            } else {
                $enviado = false;
            }

            // d. Marca o resultado do envio
            $upd_cred->execute([
                ':env' => $enviado ? 1 : 0,
                ':em'  => $enviado ? date('Y-m-d H:i:s') : null,
                ':id'  => (int) $alvo['usuario_id'],
            ]);

            $linhas[] = [
                'nome'          => (string) $alvo['nome'],
                'rm'            => (string) $alvo['rm'],
                'email'         => (string) $alvo['email'],
                'senha'         => $nova_senha,
                'email_enviado' => $enviado ? 'sim' : 'não',
            ];
        }

        // 6. Transmite o CSV como download direto.
        $data = date('Y-m-d');
        $nome_arquivo = count($linhas) === 1
            ? 'credenciais-' . preg_replace('/[^A-Za-z0-9_-]/', '', $linhas[0]['rm']) . '-' . $data . '.csv'
            : 'credenciais-lote-' . $data . '.csv';

        $csv = montar_csv_credenciais($linhas);

        // Garante que nenhum buffer anterior corrompa o binário do arquivo
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
        header('Content-Length: ' . strlen($csv));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo $csv;
        exit;
    }

    // Cadastro: renderizado INLINE (sem redirect) para exibir a senha uma única vez
    if ($acao === 'cadastrar') {
        $nome       = trim($_POST['nome'] ?? '');
        $rm         = trim($_POST['rm'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $turma_id   = filter_var($_POST['turma_id'] ?? null, FILTER_VALIDATE_INT);
        $ano_letivo = filter_var($_POST['ano_letivo'] ?? null, FILTER_VALIDATE_INT);

        // Preserva o que foi digitado para repopular o formulário em caso de erro
        $valores = [
            'nome'       => $nome,
            'rm'         => $rm,
            'email'      => $email,
            'turma_id'   => $turma_id ?: '',
            'ano_letivo' => $ano_letivo ?: '',
        ];

        // 1. Obrigatoriedade de todos os campos
        if ($nome === '' || $rm === '' || $email === '' || !$turma_id || !$ano_letivo) {
            $erros[] = 'Preencha todos os campos obrigatórios.';
        }

        // 2. E-mail válido
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $erros[] = 'Informe um e-mail válido.';
        }

        // 3. RM apenas numérico — o RM é o login; letras criam contas
        //    visualmente ambíguas (99001 vs 99OO1). Barreira de SERVIDOR:
        //    vale mesmo que o pattern do HTML seja removido via DevTools.
        if ($rm !== '' && !ctype_digit($rm)) {
            $erros[] = 'O RM deve conter apenas números';
        }

        // 4. RM duplicado — verifica usuarios.login E alunos.rm em uma só consulta
        if ($rm !== '') {
            $checa = $pdo->prepare(
                'SELECT
                    EXISTS(SELECT 1 FROM usuarios WHERE login = :rm_u) AS em_usuarios,
                    EXISTS(SELECT 1 FROM alunos   WHERE rm    = :rm_a) AS em_alunos'
            );
            $checa->execute([':rm_u' => $rm, ':rm_a' => $rm]);
            $dup = $checa->fetch();
            if ($dup && ((int) $dup['em_usuarios'] === 1 || (int) $dup['em_alunos'] === 1)) {
                $erros[] = 'Já existe um aluno com o RM informado.';
            }
        }

        if (!$erros) {
            // 1. Senha provisória aleatória (texto puro vive só neste request)
            $senha = gerar_senha_aleatoria();

            try {
                $pdo->beginTransaction();

                // 2. Usuário de autenticação: só o hash vai ao banco
                $stmt = $pdo->prepare(
                    'INSERT INTO usuarios (nome, login, email, senha, tipo, senha_alterada, ativo)
                     VALUES (:nome, :login, :email, :senha, :tipo, 0, 1)'
                );
                $stmt->execute([
                    ':nome'  => $nome,
                    ':login' => $rm,
                    ':email' => $email,
                    ':senha' => password_hash($senha, PASSWORD_DEFAULT),
                    ':tipo'  => 'aluno',
                ]);

                // 3. Id gerado
                $usuario_id = (int) $pdo->lastInsertId();

                // 4. Dados escolares vinculados ao usuário e à turma
                $stmt = $pdo->prepare(
                    'INSERT INTO alunos (usuario_id, turma_id, rm, ano_letivo, ativo)
                     VALUES (:uid, :turma, :rm, :ano, 1)'
                );
                $stmt->execute([
                    ':uid'   => $usuario_id,
                    ':turma' => $turma_id,
                    ':rm'    => $rm,
                    ':ano'   => $ano_letivo,
                ]);

                $pdo->commit();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                // Violação de UNIQUE por corrida entre a checagem e o INSERT
                $erros[]    = 'Já existe um aluno com o RM informado.';
                $usuario_id = null;
            }

            if (!$erros && $usuario_id) {
                // Cadastro já é durável. Envio de e-mail e marcação são best-effort
                // e NUNCA cancelam o cadastro — o CSV é o canal principal.
                // 5. Envio de credenciais por e-mail (só se o provedor existir)
                if ($email_configurado) {
                    $enviado      = enviar_credenciais($email, $nome, $rm, $senha);
                    $estado_email = $enviado ? 'enviado' : 'falha';
                } else {
                    $enviado      = false;
                    $estado_email = 'nao_configurado';
                }

                // 6. Marca o resultado do envio (secundário ao cadastro)
                try {
                    $pdo->prepare(
                        'UPDATE usuarios
                            SET credenciais_enviadas = :env, credenciais_enviadas_em = :em
                          WHERE id = :id'
                    )->execute([
                        ':env' => $enviado ? 1 : 0,
                        ':em'  => $enviado ? date('Y-m-d H:i:s') : null,
                        ':id'  => $usuario_id,
                    ]);
                } catch (PDOException $e) {
                    // Marcação é secundária; o cadastro já está persistido.
                }

                $credenciais = [
                    'nome'         => $nome,
                    'rm'           => $rm,
                    'email'        => $email,
                    'senha'        => $senha,
                    'estado_email' => $estado_email,
                    'enviado'      => $enviado,
                ];

                // Limpa o formulário para o próximo cadastro
                $valores = ['nome' => '', 'rm' => '', 'email' => '', 'turma_id' => '', 'ano_letivo' => (int) date('Y')];
            }
        }
    }
}

// Turmas ativas para o select de cadastro
$turmas_ativas = $pdo->query(
    'SELECT id, nome, curso FROM turmas WHERE ativo = 1 ORDER BY ano_letivo DESC, nome ASC'
)->fetchAll();

// Listagem de alunos (inclui e-mail e status de credenciais)
$alunos = $pdo->query(
    'SELECT a.id, u.nome AS nome, u.email AS email, a.rm, t.nome AS turma,
            a.ativo, u.credenciais_enviadas
       FROM alunos a
       JOIN usuarios u ON u.id = a.usuario_id
       JOIN turmas   t ON t.id = a.turma_id
      ORDER BY a.ativo DESC, u.nome ASC'
)->fetchAll();

$msg = $_GET['msg'] ?? '';

// Dados que o botão de CSV usa no cliente (senha só existe neste render)
$csv_dados = $credenciais ? [
    'nome'          => $credenciais['nome'],
    'rm'            => $credenciais['rm'],
    'email'         => $credenciais['email'],
    'senha'         => $credenciais['senha'],
    'email_enviado' => $credenciais['enviado'] ? 'Sim' : 'Não',
    'rm_arquivo'    => preg_replace('/[^A-Za-z0-9_-]/', '', $credenciais['rm']),
    'data'          => date('Y-m-d'),
] : null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Alunos · SIAetec Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* Escopo local desta página — não altera o CSS global */
        .admin-grid { display: grid; grid-template-columns: 320px 1fr; gap: 1.5rem; align-items: start; }
        .admin-form .grupo-campo { margin-bottom: 1rem; }
        .admin-form h2, .admin-lista h2 { font-size: 1.25rem; margin-bottom: 1rem; }
        .tabela { width: 100%; border-collapse: collapse; }
        .tabela th, .tabela td {
            text-align: left; padding: 0.65rem 0.5rem;
            border-bottom: 1px solid var(--cinza-borda); font-size: 0.9rem;
        }
        .tabela th { color: var(--cinza-texto); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.02em; }
        .selo { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .selo-ativo   { background-color: rgba(46,125,50,0.12); color: var(--verde-sucesso); }
        .selo-inativo { background-color: rgba(74,85,92,0.12); color: var(--cinza-texto); }
        .acao-link { background: none; border: none; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: var(--vermelho-institucional); padding: 0; }
        .dica { font-size: 0.75rem; color: var(--cinza-texto); margin-top: 0.25rem; }
        .col-vazia { color: var(--cinza-medio); }

        /* Bloco de credenciais pós-cadastro (exibição única da senha) */
        .bloco-credenciais { border-left: 4px solid var(--verde-sucesso); margin-bottom: 1.5rem; }
        .bloco-credenciais h2 { font-size: 1.25rem; margin-bottom: 1rem; }
        .cred-linha { font-size: 0.95rem; margin-bottom: 0.4rem; }
        .cred-linha strong { color: var(--cinza-texto); font-weight: 600; }
        .cred-senha {
            display: inline-block;
            font-family: ui-monospace, 'Cascadia Code', Consolas, monospace;
            font-size: 1.4rem; font-weight: 700; letter-spacing: 0.08em;
            background: var(--cinza-claro); border: 1px solid var(--cinza-borda);
            border-radius: var(--raio-borda-pequeno); padding: 0.4rem 0.9rem;
            user-select: all; margin-left: 0.25rem;
        }
        .aviso-senha { font-size: 0.8rem; color: var(--vermelho-institucional); font-weight: 600; margin: 0.9rem 0 1.1rem; }
        .status-email {
            display: inline-block; font-size: 0.85rem; font-weight: 600;
            margin: 0.9rem 0; padding: 0.5rem 0.8rem; border-radius: var(--raio-borda-pequeno);
        }
        .status-email--ok     { color: var(--verde-sucesso);    background: rgba(46,125,50,0.10); }
        .status-email--info   { color: var(--cinza-texto);      background: var(--cinza-claro); }
        .status-email--alerta { color: var(--amarelo-atencao);  background: rgba(232,161,0,0.12); }
        .cred-acoes .botao-primario { width: auto; }

        /* Barra de ações em lote */
        .lote-barra { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .botao-lote { width: auto; }
        .contador-selecao { font-size: 0.85rem; color: var(--cinza-texto); font-weight: 500; }

        /* Coluna de seleção */
        .tabela th.col-sel, .tabela td.col-sel { width: 1%; white-space: nowrap; text-align: center; }
        .chk-aluno, .chk-todos { width: 1.05rem; height: 1.05rem; cursor: pointer; accent-color: var(--vermelho-atrativo); }

        /* Ações por linha */
        .acoes-linha { display: flex; gap: 0.9rem; align-items: center; }
        .acoes-linha form { display: inline; }
        .botao-mini { background: none; border: none; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: var(--vermelho-institucional); padding: 0; }
        .botao-mini:hover { text-decoration: underline; }

        /* Modal de reautenticação (CSS/JS puro — sem confirm()) */
        .modal-overlay { position: fixed; inset: 0; background: rgba(20,20,20,0.55); display: flex; align-items: center; justify-content: center; padding: 1rem; z-index: 1000; }
        .modal-overlay[hidden] { display: none; }
        .modal-caixa { background: var(--branco); border-radius: var(--raio-borda-medio); box-shadow: var(--sombra-cartao); padding: 1.75rem; width: 100%; max-width: 440px; }
        .modal-caixa h2 { font-size: 1.2rem; margin-bottom: 0.75rem; }
        .modal-texto { font-size: 0.9rem; color: var(--cinza-texto); margin-bottom: 1.1rem; line-height: 1.55; }
        .modal-acoes { display: flex; gap: 0.75rem; margin-top: 1.25rem; }
        .modal-acoes .botao-primario, .modal-acoes .botao-secundario { width: 50%; }

        @media (max-width: 1023px) { .admin-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar-admin.php'; ?>

    <main class="conteudo">
        <h1 style="margin-bottom:1.25rem;">Alunos</h1>

        <?php if ($msg === 'status'): ?>
            <p class="mensagem-sucesso">Status do aluno atualizado.</p>
        <?php elseif ($msg === 'senha_incorreta'): ?>
            <p class="mensagem-erro">Senha incorreta.</p>
        <?php elseif ($msg === 'regenerar_invalido'): ?>
            <p class="mensagem-erro">Nenhum aluno ativo válido foi encontrado para a regeneração.</p>
        <?php elseif ($msg === 'regenerar_vazio'): ?>
            <p class="mensagem-erro">Selecione ao menos um aluno para regenerar as credenciais.</p>
        <?php endif; ?>

        <?php foreach ($erros as $erro): ?>
            <p class="mensagem-erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endforeach; ?>

        <?php if ($credenciais): ?>
            <section class="cartao bloco-credenciais">
                <h2>Aluno cadastrado</h2>
                <p class="cred-linha"><strong>Aluno:</strong> <?= htmlspecialchars($credenciais['nome'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="cred-linha"><strong>RM:</strong> <?= htmlspecialchars($credenciais['rm'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="cred-linha">
                    <strong>Senha provisória:</strong>
                    <span class="cred-senha"><?= htmlspecialchars($credenciais['senha'], ENT_QUOTES, 'UTF-8') ?></span>
                </p>

                <?php if ($credenciais['estado_email'] === 'enviado'): ?>
                    <div><span class="status-email status-email--ok">✓ E-mail enviado</span></div>
                <?php elseif ($credenciais['estado_email'] === 'nao_configurado'): ?>
                    <div><span class="status-email status-email--info">ℹ Envio de e-mail não configurado — entregue as credenciais pelo CSV</span></div>
                <?php else: ?>
                    <div><span class="status-email status-email--alerta">⚠ Falha no envio — entregue as credenciais pelo CSV</span></div>
                <?php endif; ?>

                <p class="aviso-senha">Esta é a única oportunidade de visualizar a senha em texto puro. Baixe o CSV ou anote agora.</p>

                <div class="cred-acoes">
                    <button type="button" class="botao-primario" onclick="baixarCSV()">Baixar CSV</button>
                </div>
            </section>
        <?php endif; ?>

        <div class="admin-grid">
            <section class="cartao admin-form">
                <h2>Novo aluno</h2>
                <?php if (!$turmas_ativas): ?>
                    <p class="mensagem-erro">Cadastre uma turma ativa antes de adicionar alunos.</p>
                <?php else: ?>
                <form method="post" action="alunos.php" autocomplete="off">
                    <input type="hidden" name="acao" value="cadastrar">
                    <div class="grupo-campo">
                        <label class="rotulo" for="nome">Nome completo</label>
                        <input class="campo" type="text" id="nome" name="nome" required
                               value="<?= htmlspecialchars($valores['nome'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="rm">RM</label>
                        <input class="campo" type="text" id="rm" name="rm" required
                               inputmode="numeric" pattern="[0-9]+" maxlength="20"
                               title="O RM deve conter apenas números"
                               value="<?= htmlspecialchars($valores['rm'], ENT_QUOTES, 'UTF-8') ?>">
                        <span class="dica">Somente números. Uma senha provisória aleatória será gerada no cadastro.</span>
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="email">E-mail</label>
                        <input class="campo" type="email" id="email" name="email" required
                               value="<?= htmlspecialchars($valores['email'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="turma_id">Turma</label>
                        <select class="campo" id="turma_id" name="turma_id" required>
                            <option value="" disabled <?= $valores['turma_id'] === '' ? 'selected' : '' ?>>Selecione…</option>
                            <?php foreach ($turmas_ativas as $t): ?>
                                <option value="<?= (int) $t['id'] ?>" <?= (int) $valores['turma_id'] === (int) $t['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['nome'] . ' — ' . $t['curso'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="ano_letivo">Ano letivo</label>
                        <input class="campo" type="number" id="ano_letivo" name="ano_letivo" required
                               min="2020" max="2099"
                               value="<?= htmlspecialchars((string) ($valores['ano_letivo'] ?: date('Y')), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <button type="submit" class="botao-primario">Cadastrar</button>
                </form>
                <?php endif; ?>
            </section>

            <section class="cartao admin-lista">
                <h2>Alunos cadastrados</h2>

                <?php if ($alunos): ?>
                <div class="lote-barra">
                    <button type="button" id="btn-regenerar-lote" class="botao-primario botao-lote"
                            onclick="abrirModalLote()" disabled>Regenerar credenciais</button>
                    <span id="contador-selecao" class="contador-selecao">Nenhum aluno selecionado</span>
                </div>
                <?php endif; ?>

                <table class="tabela">
                    <thead>
                        <tr>
                            <th class="col-sel">
                                <input type="checkbox" id="chk-todos" class="chk-todos" aria-label="Selecionar todos">
                            </th>
                            <th>Nome</th><th>RM</th><th>E-mail</th><th>Turma</th><th>Credenciais</th><th>Status</th><th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$alunos): ?>
                            <tr><td colspan="8">Nenhum aluno cadastrado.</td></tr>
                        <?php else: foreach ($alunos as $a): ?>
                            <tr>
                                <td class="col-sel">
                                    <?php if ($a['ativo']): ?>
                                        <input type="checkbox" class="chk-aluno" value="<?= (int) $a['id'] ?>"
                                               aria-label="Selecionar <?= htmlspecialchars($a['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($a['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($a['rm'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if (!empty($a['email'])): ?>
                                        <?= htmlspecialchars($a['email'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        <span class="col-vazia">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($a['turma'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="selo <?= $a['credenciais_enviadas'] ? 'selo-ativo' : 'selo-inativo' ?>">
                                        <?= $a['credenciais_enviadas'] ? 'Enviado' : 'Pendente' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="selo <?= $a['ativo'] ? 'selo-ativo' : 'selo-inativo' ?>">
                                        <?= $a['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="acoes-linha">
                                        <?php if ($a['ativo']): ?>
                                            <button type="button" class="botao-mini"
                                                    onclick="abrirModalIndividual(<?= (int) $a['id'] ?>)">Regenerar</button>
                                        <?php endif; ?>
                                        <form method="post" action="alunos.php" onsubmit="return confirm('Confirmar alteração de status?');">
                                            <input type="hidden" name="acao" value="alternar_status">
                                            <input type="hidden" name="aluno_id" value="<?= (int) $a['id'] ?>">
                                            <button type="submit" class="acao-link">
                                                <?= $a['ativo'] ? 'Inativar' : 'Reativar' ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>

    <?php if ($alunos): ?>
    <!-- Modal de confirmação com reautenticação (CSS/JS puro — sem confirm()) -->
    <div id="modal-regenerar" class="modal-overlay" hidden>
        <div class="modal-caixa" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
            <h2 id="modal-titulo">Confirmar regeneração</h2>
            <p class="modal-texto">
                As senhas atuais de <strong id="modal-qtd">0</strong> aluno(s) serão invalidadas.
                Confirme sua senha para continuar.
            </p>
            <form method="post" action="alunos.php" id="form-regenerar" autocomplete="off">
                <input type="hidden" name="acao" value="regenerar">
                <div id="modal-ids"><!-- ids injetados por JS como inputs hidden --></div>
                <div class="grupo-campo">
                    <label class="rotulo" for="senha_admin">Sua senha</label>
                    <input class="campo" type="password" id="senha_admin" name="senha_admin"
                           required autocomplete="current-password">
                </div>
                <div class="modal-acoes">
                    <button type="button" class="botao-secundario" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="botao-primario">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const chkTodos = document.getElementById('chk-todos');
            const chks     = Array.prototype.slice.call(document.querySelectorAll('.chk-aluno'));
            const btnLote  = document.getElementById('btn-regenerar-lote');
            const contador = document.getElementById('contador-selecao');
            const modal    = document.getElementById('modal-regenerar');

            function selecionados() {
                return chks.filter(function (c) { return c.checked; });
            }

            function atualizar() {
                const n = selecionados().length;
                if (btnLote) { btnLote.disabled = (n === 0); }
                if (contador) {
                    contador.textContent = n === 0 ? 'Nenhum aluno selecionado'
                        : (n === 1 ? '1 aluno selecionado' : n + ' alunos selecionados');
                }
                if (chkTodos) {
                    chkTodos.checked = (n > 0 && n === chks.length);
                    chkTodos.indeterminate = (n > 0 && n < chks.length);
                }
            }

            if (chkTodos) {
                chkTodos.addEventListener('change', function () {
                    chks.forEach(function (c) { c.checked = chkTodos.checked; });
                    atualizar();
                });
            }
            chks.forEach(function (c) { c.addEventListener('change', atualizar); });

            function abrirModal(ids) {
                const cont = document.getElementById('modal-ids');
                cont.innerHTML = '';
                ids.forEach(function (id) {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'ids[]';
                    inp.value = id;
                    cont.appendChild(inp);
                });
                document.getElementById('modal-qtd').textContent = ids.length;
                modal.hidden = false;
                document.getElementById('senha_admin').focus();
            }

            window.abrirModalLote = function () {
                const ids = selecionados().map(function (c) { return c.value; });
                if (ids.length === 0) { return; }
                abrirModal(ids);
            };

            window.abrirModalIndividual = function (id) {
                abrirModal([String(id)]);
            };

            window.fecharModal = function () {
                modal.hidden = true;
                document.getElementById('senha_admin').value = '';
                document.getElementById('modal-ids').innerHTML = '';
            };

            // Fecha no ESC e no clique fora da caixa
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.hidden) { window.fecharModal(); }
            });
            modal.addEventListener('mousedown', function (e) {
                if (e.target === modal) { window.fecharModal(); }
            });

            // A resposta do submit é um download (attachment): a página não navega.
            // Limpa o modal depois que a requisição já foi disparada.
            document.getElementById('form-regenerar').addEventListener('submit', function () {
                setTimeout(window.fecharModal, 1500);
            });

            atualizar();
        })();
    </script>
    <?php endif; ?>

    <?php if ($csv_dados): ?>
    <script>
        // Credenciais deste cadastro. A senha vive só neste render: não vai para
        // session, arquivo ou banco. O CSV é gerado 100% no cliente.
        const CRED = <?= json_encode($csv_dados, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

        function csvEscape(valor) {
            let s = String(valor == null ? '' : valor);
            // Proteção contra injeção de fórmula (Excel/Sheets)
            if (/^[=+\-@\t\r]/.test(s)) {
                s = "'" + s;
            }
            // Aspas/;/quebra de linha exigem envolver em aspas e dobrar as aspas
            if (/[";\r\n]/.test(s)) {
                s = '"' + s.replace(/"/g, '""') + '"';
            }
            return s;
        }

        function baixarCSV() {
            const cabecalho = ['nome', 'rm', 'email', 'senha', 'email_enviado'];
            const linha = [CRED.nome, CRED.rm, CRED.email, CRED.senha, CRED.email_enviado];

            // BOM UTF-8 + separador ';' (Excel PT-BR) + fim de linha CRLF
            const conteudo = '\uFEFF'
                + cabecalho.join(';') + '\r\n'
                + linha.map(csvEscape).join(';') + '\r\n';

            const blob = new Blob([conteudo], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'credenciais-' + CRED.rm_arquivo + '-' + CRED.data + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }
    </script>
    <?php endif; ?>
</body>
</html>
