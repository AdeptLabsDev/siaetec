<?php
/**
 * admin/refeicoes.php
 * Cadastro e listagem de refeições. O status (aberta/encerrada) é
 * calculado dinamicamente com enquete_aberta() a cada exibição.
 */

require_once __DIR__ . '/../includes/auth.php'; // sessão (antes de qualquer HTML)
require_once __DIR__ . '/../includes/db.php';   // $pdo

verificar_sessao('admin');
$usuario = usuario_logado();

// --- Processamento de cadastro (POST → PRG) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo        = trim($_POST['titulo'] ?? '');
    $descricao     = trim($_POST['descricao'] ?? '');
    $imagem        = trim($_POST['imagem'] ?? '');
    $data_refeicao = trim($_POST['data_refeicao'] ?? '');
    $horario_bruto = trim($_POST['horario_limite'] ?? '');

    // O input datetime-local envia 'YYYY-MM-DDTHH:MM' — normaliza para DATETIME
    $horario_limite = $horario_bruto !== '' ? str_replace('T', ' ', $horario_bruto) . ':00' : '';

    $data_ok    = (bool) strtotime($data_refeicao);
    $horario_ok = (bool) strtotime($horario_limite);

    if ($titulo === '' || !$data_ok || !$horario_ok) {
        redirecionar('refeicoes.php?msg=erro');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO refeicoes (admin_id, titulo, descricao, imagem, data_refeicao, horario_limite)
         VALUES (:admin, :titulo, :descricao, :imagem, :data, :horario)'
    );
    $stmt->execute([
        ':admin'     => $usuario['usuario_id'],
        ':titulo'    => $titulo,
        ':descricao' => $descricao !== '' ? $descricao : null,
        ':imagem'    => $imagem !== '' ? $imagem : null,
        ':data'      => $data_refeicao,
        ':horario'   => $horario_limite,
    ]);
    redirecionar('refeicoes.php?msg=cadastrada');
}

// Listagem
$refeicoes = $pdo->query(
    'SELECT id, titulo, data_refeicao, horario_limite
       FROM refeicoes
      ORDER BY data_refeicao DESC, horario_limite DESC'
)->fetchAll();

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Refeições · SIAetec Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .admin-grid { display: grid; grid-template-columns: 340px 1fr; gap: 1.5rem; align-items: start; }
        .admin-form .grupo-campo { margin-bottom: 1rem; }
        .admin-form h2, .admin-lista h2 { font-size: 1.25rem; margin-bottom: 1rem; }
        .area-texto {
            width: 100%; min-height: 90px; resize: vertical;
            font-family: var(--fonte-principal); font-size: 16px; color: var(--preto);
            background-color: var(--branco); border: 1px solid var(--cinza-borda);
            border-radius: var(--raio-borda-pequeno); padding: 0.7rem 0.85rem;
        }
        .area-texto:focus { outline: none; border-color: var(--vermelho-atrativo); }
        .tabela { width: 100%; border-collapse: collapse; }
        .tabela th, .tabela td {
            text-align: left; padding: 0.65rem 0.5rem;
            border-bottom: 1px solid var(--cinza-borda); font-size: 0.9rem;
        }
        .tabela th { color: var(--cinza-texto); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.02em; }
        .selo { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .selo-aberta    { background-color: rgba(46,125,50,0.12); color: var(--verde-sucesso); }
        .selo-encerrada { background-color: rgba(74,85,92,0.12); color: var(--cinza-texto); }
        @media (max-width: 1023px) { .admin-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar-admin.php'; ?>

    <main class="conteudo">
        <h1 style="margin-bottom:1.25rem;">Refeições</h1>

        <?php if ($msg === 'cadastrada'): ?>
            <p class="mensagem-sucesso">Refeição cadastrada com sucesso.</p>
        <?php elseif ($msg === 'erro'): ?>
            <p class="mensagem-erro">Verifique título, data e horário limite e tente novamente.</p>
        <?php endif; ?>

        <div class="admin-grid">
            <section class="cartao admin-form">
                <h2>Nova refeição</h2>
                <form method="post" action="refeicoes.php">
                    <div class="grupo-campo">
                        <label class="rotulo" for="titulo">Título</label>
                        <input class="campo" type="text" id="titulo" name="titulo" required placeholder="Ex.: Almoço de terça">
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="descricao">Descrição do cardápio</label>
                        <textarea class="area-texto" id="descricao" name="descricao" rows="3"></textarea>
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="imagem">Imagem (.webp em /assets/img)</label>
                        <input class="campo" type="text" id="imagem" name="imagem" placeholder="Ex.: feijoada.webp">
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="data_refeicao">Data da refeição</label>
                        <input class="campo" type="date" id="data_refeicao" name="data_refeicao" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="horario_limite">Horário limite para resposta</label>
                        <input class="campo" type="datetime-local" id="horario_limite" name="horario_limite" required>
                    </div>
                    <button type="submit" class="botao-primario">Cadastrar</button>
                </form>
            </section>

            <section class="cartao admin-lista">
                <h2>Refeições cadastradas</h2>
                <table class="tabela">
                    <thead>
                        <tr><th>Data</th><th>Título</th><th>Horário limite</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$refeicoes): ?>
                            <tr><td colspan="4">Nenhuma refeição cadastrada.</td></tr>
                        <?php else: foreach ($refeicoes as $r):
                            $aberta = enquete_aberta($r['horario_limite']); ?>
                            <tr>
                                <td><?= htmlspecialchars(formatar_data($r['data_refeicao'], 'd/m/Y'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($r['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(formatar_data($r['horario_limite'], 'd/m/Y H:i'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="selo <?= $aberta ? 'selo-aberta' : 'selo-encerrada' ?>">
                                        <?= $aberta ? 'Aberta' : 'Encerrada' ?>
                                    </span>
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
