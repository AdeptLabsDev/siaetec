<?php
/**
 * admin/turmas.php
 * Cadastro e listagem de turmas. Turmas não são deletadas — apenas
 * inativadas/reativadas, preservando o histórico (context.md §7.7).
 */

require_once __DIR__ . '/../includes/auth.php'; // sessão (antes de qualquer HTML)
require_once __DIR__ . '/../includes/db.php';   // $pdo

verificar_sessao('admin');
$usuario = usuario_logado();

$PERIODOS = ['manhã', 'tarde', 'noite'];

// --- Processamento de ações (POST → PRG) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'cadastrar';

    if ($acao === 'cadastrar') {
        $nome       = trim($_POST['nome'] ?? '');
        $curso      = trim($_POST['curso'] ?? '');
        $periodo    = $_POST['periodo'] ?? '';
        $ano_letivo = filter_var($_POST['ano_letivo'] ?? null, FILTER_VALIDATE_INT);

        if ($nome === '' || $curso === '' || !in_array($periodo, $PERIODOS, true) || !$ano_letivo) {
            redirecionar('turmas.php?msg=erro');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO turmas (nome, curso, periodo, ano_letivo)
             VALUES (:nome, :curso, :periodo, :ano)'
        );
        $stmt->execute([
            ':nome'    => $nome,
            ':curso'   => $curso,
            ':periodo' => $periodo,
            ':ano'     => $ano_letivo,
        ]);
        redirecionar('turmas.php?msg=cadastrada');
    }

    if ($acao === 'alternar_status') {
        $turma_id = filter_var($_POST['turma_id'] ?? null, FILTER_VALIDATE_INT);
        if ($turma_id) {
            $stmt = $pdo->prepare('UPDATE turmas SET ativo = 1 - ativo WHERE id = :id');
            $stmt->execute([':id' => $turma_id]);
        }
        redirecionar('turmas.php?msg=status');
    }

    redirecionar('turmas.php');
}

// Listagem (ativas primeiro, depois por ano e nome)
$turmas = $pdo->query(
    'SELECT id, nome, curso, periodo, ano_letivo, ativo
       FROM turmas
      ORDER BY ativo DESC, ano_letivo DESC, nome ASC'
)->fetchAll();

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Turmas · SIAetec Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* --- Estilos comuns das telas de gestão do admin --- */
        .admin-grid { display: grid; grid-template-columns: 320px 1fr; gap: 1.5rem; align-items: start; }
        .admin-form .grupo-campo { margin-bottom: 1rem; }
        .admin-form h2, .admin-lista h2 { font-size: 1.25rem; margin-bottom: 1rem; }

        .tabela { width: 100%; border-collapse: collapse; }
        .tabela th, .tabela td {
            text-align: left;
            padding: 0.65rem 0.5rem;
            border-bottom: 1px solid var(--cinza-borda);
            font-size: 0.9rem;
        }
        .tabela th { color: var(--cinza-texto); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.02em; }
        .selo { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .selo-ativo   { background-color: rgba(46,125,50,0.12); color: var(--verde-sucesso); }
        .selo-inativo { background-color: rgba(74,85,92,0.12); color: var(--cinza-texto); }
        .acao-link { background: none; border: none; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: var(--vermelho-institucional); padding: 0; }

        @media (max-width: 1023px) {
            .admin-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar-admin.php'; ?>

    <main class="conteudo">
        <h1 style="margin-bottom:1.25rem;">Turmas</h1>

        <?php if ($msg === 'cadastrada'): ?>
            <p class="mensagem-sucesso">Turma cadastrada com sucesso.</p>
        <?php elseif ($msg === 'status'): ?>
            <p class="mensagem-sucesso">Status da turma atualizado.</p>
        <?php elseif ($msg === 'erro'): ?>
            <p class="mensagem-erro">Verifique os dados informados e tente novamente.</p>
        <?php endif; ?>

        <div class="admin-grid">
            <section class="cartao admin-form">
                <h2>Nova turma</h2>
                <form method="post" action="turmas.php">
                    <input type="hidden" name="acao" value="cadastrar">
                    <div class="grupo-campo">
                        <label class="rotulo" for="nome">Nome</label>
                        <input class="campo" type="text" id="nome" name="nome" required placeholder="Ex.: 1A">
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="curso">Curso</label>
                        <input class="campo" type="text" id="curso" name="curso" required placeholder="Ex.: Técnico em Informática">
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="periodo">Período</label>
                        <select class="campo" id="periodo" name="periodo" required>
                            <option value="manhã">Manhã</option>
                            <option value="tarde">Tarde</option>
                            <option value="noite">Noite</option>
                        </select>
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="ano_letivo">Ano letivo</label>
                        <input class="campo" type="number" id="ano_letivo" name="ano_letivo" required
                               min="2020" max="2099" value="<?= (int) date('Y') ?>">
                    </div>
                    <button type="submit" class="botao-primario">Cadastrar</button>
                </form>
            </section>

            <section class="cartao admin-lista">
                <h2>Turmas cadastradas</h2>
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Nome</th><th>Curso</th><th>Período</th><th>Ano</th><th>Status</th><th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$turmas): ?>
                            <tr><td colspan="6">Nenhuma turma cadastrada.</td></tr>
                        <?php else: foreach ($turmas as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($t['curso'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(ucfirst($t['periodo']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $t['ano_letivo'] ?></td>
                                <td>
                                    <span class="selo <?= $t['ativo'] ? 'selo-ativo' : 'selo-inativo' ?>">
                                        <?= $t['ativo'] ? 'Ativa' : 'Inativa' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="turmas.php" onsubmit="return confirm('Confirmar alteração de status?');">
                                        <input type="hidden" name="acao" value="alternar_status">
                                        <input type="hidden" name="turma_id" value="<?= (int) $t['id'] ?>">
                                        <button type="submit" class="acao-link">
                                            <?= $t['ativo'] ? 'Inativar' : 'Reativar' ?>
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
