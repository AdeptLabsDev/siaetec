<?php
/**
 * admin/alunos.php
 * Cadastro e listagem de alunos. Cada aluno gera um registro em `usuarios`
 * (login = RM, senha padrão = RM, senha_alterada = 0) e outro em `alunos`.
 * Alunos não são deletados — apenas inativados/reativados.
 */

require_once __DIR__ . '/../includes/auth.php'; // sessão (antes de qualquer HTML)
require_once __DIR__ . '/../includes/db.php';   // $pdo

verificar_sessao('admin');
$usuario = usuario_logado();

// --- Processamento de ações (POST → PRG) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'cadastrar';

    if ($acao === 'cadastrar') {
        $nome       = trim($_POST['nome'] ?? '');
        $rm         = trim($_POST['rm'] ?? '');
        $turma_id   = filter_var($_POST['turma_id'] ?? null, FILTER_VALIDATE_INT);
        $ano_letivo = filter_var($_POST['ano_letivo'] ?? null, FILTER_VALIDATE_INT);

        if ($nome === '' || $rm === '' || !$turma_id || !$ano_letivo) {
            redirecionar('alunos.php?msg=erro');
        }

        try {
            $pdo->beginTransaction();

            // Usuário de autenticação: senha padrão = RM, primeiro acesso obrigatório
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (nome, login, senha, tipo, senha_alterada, ativo)
                 VALUES (:nome, :login, :senha, :tipo, 0, 1)'
            );
            $stmt->execute([
                ':nome'  => $nome,
                ':login' => $rm,
                ':senha' => password_hash($rm, PASSWORD_DEFAULT),
                ':tipo'  => 'aluno',
            ]);
            $usuario_id = (int) $pdo->lastInsertId();

            // Dados escolares
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
            redirecionar('alunos.php?msg=cadastrado');
        } catch (PDOException $e) {
            $pdo->rollBack();
            // Provável RM/login duplicado (violação de UNIQUE)
            redirecionar('alunos.php?msg=duplicado');
        }
    }

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
                $pdo->rollBack();
            }
        }
        redirecionar('alunos.php?msg=status');
    }

    redirecionar('alunos.php');
}

// Turmas ativas para o select de cadastro
$turmas_ativas = $pdo->query(
    'SELECT id, nome, curso FROM turmas WHERE ativo = 1 ORDER BY ano_letivo DESC, nome ASC'
)->fetchAll();

// Listagem de alunos
$alunos = $pdo->query(
    'SELECT a.id, u.nome AS nome, a.rm, t.nome AS turma, a.ativo
       FROM alunos a
       JOIN usuarios u ON u.id = a.usuario_id
       JOIN turmas   t ON t.id = a.turma_id
      ORDER BY a.ativo DESC, u.nome ASC'
)->fetchAll();

$msg = $_GET['msg'] ?? '';
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
        @media (max-width: 1023px) { .admin-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar-admin.php'; ?>

    <main class="conteudo">
        <h1 style="margin-bottom:1.25rem;">Alunos</h1>

        <?php if ($msg === 'cadastrado'): ?>
            <p class="mensagem-sucesso">Aluno cadastrado. Senha inicial igual ao RM.</p>
        <?php elseif ($msg === 'status'): ?>
            <p class="mensagem-sucesso">Status do aluno atualizado.</p>
        <?php elseif ($msg === 'duplicado'): ?>
            <p class="mensagem-erro">Já existe um aluno com este RM.</p>
        <?php elseif ($msg === 'erro'): ?>
            <p class="mensagem-erro">Verifique os dados informados e tente novamente.</p>
        <?php endif; ?>

        <div class="admin-grid">
            <section class="cartao admin-form">
                <h2>Novo aluno</h2>
                <?php if (!$turmas_ativas): ?>
                    <p class="mensagem-erro">Cadastre uma turma ativa antes de adicionar alunos.</p>
                <?php else: ?>
                <form method="post" action="alunos.php">
                    <input type="hidden" name="acao" value="cadastrar">
                    <div class="grupo-campo">
                        <label class="rotulo" for="nome">Nome completo</label>
                        <input class="campo" type="text" id="nome" name="nome" required>
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="rm">RM</label>
                        <input class="campo" type="text" id="rm" name="rm" required>
                        <span class="dica">A senha inicial será igual ao RM.</span>
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="turma_id">Turma</label>
                        <select class="campo" id="turma_id" name="turma_id" required>
                            <?php foreach ($turmas_ativas as $t): ?>
                                <option value="<?= (int) $t['id'] ?>">
                                    <?= htmlspecialchars($t['nome'] . ' — ' . $t['curso'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="ano_letivo">Ano letivo</label>
                        <input class="campo" type="number" id="ano_letivo" name="ano_letivo" required
                               min="2020" max="2099" value="<?= (int) date('Y') ?>">
                    </div>
                    <button type="submit" class="botao-primario">Cadastrar</button>
                </form>
                <?php endif; ?>
            </section>

            <section class="cartao admin-lista">
                <h2>Alunos cadastrados</h2>
                <table class="tabela">
                    <thead>
                        <tr><th>Nome</th><th>RM</th><th>Turma</th><th>Status</th><th>Ação</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$alunos): ?>
                            <tr><td colspan="5">Nenhum aluno cadastrado.</td></tr>
                        <?php else: foreach ($alunos as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($a['rm'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($a['turma'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="selo <?= $a['ativo'] ? 'selo-ativo' : 'selo-inativo' ?>">
                                        <?= $a['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="alunos.php" onsubmit="return confirm('Confirmar alteração de status?');">
                                        <input type="hidden" name="acao" value="alternar_status">
                                        <input type="hidden" name="aluno_id" value="<?= (int) $a['id'] ?>">
                                        <button type="submit" class="acao-link">
                                            <?= $a['ativo'] ? 'Inativar' : 'Reativar' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>
</body>
</html>
