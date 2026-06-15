<?php
/**
 * admin/enquetes.php
 * CRUD de enquetes — vincula uma refeição cadastrada a uma data e horário
 * limite. Apenas uma enquete por data (UNIQUE data_enquete).
 * Exclusão só é permitida se não houver intenções registradas.
 */

require_once __DIR__ . '/../includes/auth.php'; // sessão (antes de qualquer HTML)
require_once __DIR__ . '/../includes/db.php';   // $pdo

verificar_sessao('admin');
$usuario = usuario_logado();

// --- Processamento de ações (POST → PRG) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'cadastrar';

    if ($acao === 'cadastrar') {
        $refeicao_id   = filter_var($_POST['refeicao_id'] ?? null, FILTER_VALIDATE_INT);
        $data_enquete  = trim($_POST['data_enquete'] ?? '');
        $horario_bruto = trim($_POST['horario_limite'] ?? '');

        // datetime-local envia 'YYYY-MM-DDTHH:MM' — normaliza para DATETIME
        $horario_limite = $horario_bruto !== '' ? str_replace('T', ' ', $horario_bruto) . ':00' : '';

        if (!$refeicao_id || !strtotime($data_enquete) || !strtotime($horario_limite)) {
            redirecionar('enquetes.php?msg=erro');
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO enquetes (refeicao_id, admin_id, data_enquete, horario_limite)
                 VALUES (:ref, :admin, :data, :horario)'
            );
            $stmt->execute([
                ':ref'     => $refeicao_id,
                ':admin'   => $usuario['usuario_id'],
                ':data'    => $data_enquete,
                ':horario' => $horario_limite,
            ]);
            redirecionar('enquetes.php?msg=cadastrada');
        } catch (PDOException $e) {
            // 23000 = violação de integridade (UNIQUE data_enquete já existente)
            if ($e->getCode() === '23000') {
                redirecionar('enquetes.php?msg=duplicada');
            }
            redirecionar('enquetes.php?msg=erro');
        }
    }

    if ($acao === 'excluir') {
        $enquete_id = filter_var($_POST['enquete_id'] ?? null, FILTER_VALIDATE_INT);
        if ($enquete_id) {
            // Só exclui se não houver intenções registradas
            $st = $pdo->prepare('SELECT COUNT(*) FROM intencoes_alimentares WHERE enquete_id = :id');
            $st->execute([':id' => $enquete_id]);
            if ((int) $st->fetchColumn() > 0) {
                redirecionar('enquetes.php?msg=tem_intencoes');
            }
            $pdo->prepare('DELETE FROM enquetes WHERE id = :id')->execute([':id' => $enquete_id]);
            redirecionar('enquetes.php?msg=excluida');
        }
        redirecionar('enquetes.php');
    }

    redirecionar('enquetes.php');
}

// Refeições disponíveis para o select
$refeicoes = $pdo->query('SELECT id, titulo FROM refeicoes ORDER BY titulo ASC')->fetchAll();

// Listagem de enquetes
$enquetes = $pdo->query(
    'SELECT e.id, e.data_enquete, e.horario_limite, r.titulo
       FROM enquetes e
       JOIN refeicoes r ON r.id = e.refeicao_id
      ORDER BY e.data_enquete DESC'
)->fetchAll();

$hoje = date('Y-m-d');
$msg  = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Enquetes · SIAetec Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .admin-grid { display: grid; grid-template-columns: 340px 1fr; gap: 1.5rem; align-items: start; }
        .admin-form .grupo-campo { margin-bottom: 1rem; }
        .admin-form h2, .admin-lista h2 { font-size: 1.25rem; margin-bottom: 1rem; }
        .tabela { width: 100%; border-collapse: collapse; }
        .tabela th, .tabela td {
            text-align: left; padding: 0.65rem 0.5rem;
            border-bottom: 1px solid var(--cinza-borda); font-size: 0.9rem;
        }
        .tabela th { color: var(--cinza-texto); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.02em; }
        .selo { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .selo-aberta    { background-color: rgba(46,125,50,0.12); color: var(--verde-sucesso); }
        .selo-encerrada { background-color: rgba(74,85,92,0.12); color: var(--cinza-texto); }
        .acao-link { background: none; border: none; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: var(--vermelho-atrativo); padding: 0; }
        .acao-ver { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; }
        @media (max-width: 1023px) { .admin-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar-admin.php'; ?>

    <main class="conteudo">
        <h1 style="margin-bottom:1.25rem;">Enquetes</h1>

        <?php if ($msg === 'cadastrada'): ?>
            <p class="mensagem-sucesso">Enquete criada com sucesso.</p>
        <?php elseif ($msg === 'excluida'): ?>
            <p class="mensagem-sucesso">Enquete excluída.</p>
        <?php elseif ($msg === 'duplicada'): ?>
            <p class="mensagem-erro">Já existe uma enquete para essa data. Só é permitida uma enquete por dia.</p>
        <?php elseif ($msg === 'tem_intencoes'): ?>
            <p class="mensagem-erro">Não é possível excluir: já existem respostas registradas nesta enquete.</p>
        <?php elseif ($msg === 'erro'): ?>
            <p class="mensagem-erro">Selecione uma refeição e informe data e horário limite válidos.</p>
        <?php endif; ?>

        <div class="admin-grid">
            <section class="cartao admin-form">
                <h2>Nova enquete</h2>
                <?php if (!$refeicoes): ?>
                    <p class="mensagem-erro">Cadastre uma refeição antes de criar enquetes.</p>
                <?php else: ?>
                <form method="post" action="enquetes.php">
                    <input type="hidden" name="acao" value="cadastrar">
                    <div class="grupo-campo">
                        <label class="rotulo" for="refeicao_id">Refeição</label>
                        <select class="campo" id="refeicao_id" name="refeicao_id" required>
                            <?php foreach ($refeicoes as $r): ?>
                                <option value="<?= (int) $r['id'] ?>"><?= htmlspecialchars($r['titulo'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="data_enquete">Data da enquete</label>
                        <input class="campo" type="date" id="data_enquete" name="data_enquete" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="horario_limite">Horário limite</label>
                        <input class="campo" type="datetime-local" id="horario_limite" name="horario_limite" required>
                    </div>
                    <button type="submit" class="botao-primario">Criar enquete</button>
                </form>
                <?php endif; ?>
            </section>

            <section class="cartao admin-lista">
                <h2>Enquetes cadastradas</h2>
                <table class="tabela">
                    <thead>
                        <tr><th>Data</th><th>Refeição</th><th>Horário limite</th><th>Status</th><th>Ação</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$enquetes): ?>
                            <tr><td colspan="5">Nenhuma enquete cadastrada.</td></tr>
                        <?php else: foreach ($enquetes as $e):
                            $aberta = enquete_aberta($e['horario_limite']); ?>
                            <tr>
                                <td><?= htmlspecialchars(formatar_data($e['data_enquete'], 'd/m/Y'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($e['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(formatar_data($e['horario_limite'], 'd/m/Y H:i'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="selo <?= $aberta ? 'selo-aberta' : 'selo-encerrada' ?>">
                                        <?= $aberta ? 'Aberta' : 'Encerrada' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($e['data_enquete'] === $hoje): ?>
                                        <a class="acao-ver" href="enquete.php?id=<?= (int) $e['id'] ?>">Ver resultados</a>
                                    <?php endif; ?>
                                    <form method="post" action="enquetes.php" onsubmit="return confirm('Excluir esta enquete?');">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="enquete_id" value="<?= (int) $e['id'] ?>">
                                        <button type="submit" class="acao-link">Excluir</button>
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
