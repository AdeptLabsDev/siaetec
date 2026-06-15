<?php
/**
 * admin/refeicoes.php
 * CRUD do cadastro fixo de refeições (título, descrição, imagem).
 * A data e o horário limite NÃO pertencem aqui — são da enquete.
 * Exclusão só é permitida se a refeição não estiver vinculada a enquetes.
 */

require_once __DIR__ . '/../includes/auth.php'; // sessão (antes de qualquer HTML)
require_once __DIR__ . '/../includes/db.php';   // $pdo

verificar_sessao('admin');
$usuario = usuario_logado();

// --- Processamento de ações (POST → PRG) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'cadastrar';

    if ($acao === 'cadastrar') {
        $titulo    = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $imagem    = trim($_POST['imagem'] ?? '');

        if ($titulo === '') {
            redirecionar('refeicoes.php?msg=erro');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO refeicoes (admin_id, titulo, descricao, imagem)
             VALUES (:admin, :titulo, :descricao, :imagem)'
        );
        $stmt->execute([
            ':admin'     => $usuario['usuario_id'],
            ':titulo'    => $titulo,
            ':descricao' => $descricao !== '' ? $descricao : null,
            ':imagem'    => $imagem !== '' ? $imagem : null,
        ]);
        redirecionar('refeicoes.php?msg=cadastrada');
    }

    if ($acao === 'excluir') {
        $refeicao_id = filter_var($_POST['refeicao_id'] ?? null, FILTER_VALIDATE_INT);
        if ($refeicao_id) {
            // Só exclui se não houver enquetes vinculadas
            $st = $pdo->prepare('SELECT COUNT(*) FROM enquetes WHERE refeicao_id = :id');
            $st->execute([':id' => $refeicao_id]);
            if ((int) $st->fetchColumn() > 0) {
                redirecionar('refeicoes.php?msg=vinculada');
            }
            $pdo->prepare('DELETE FROM refeicoes WHERE id = :id')->execute([':id' => $refeicao_id]);
            redirecionar('refeicoes.php?msg=excluida');
        }
        redirecionar('refeicoes.php');
    }

    redirecionar('refeicoes.php');
}

// Listagem
$refeicoes = $pdo->query(
    'SELECT id, titulo, descricao, imagem FROM refeicoes ORDER BY titulo ASC'
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
        .dica { font-size: 0.75rem; color: var(--cinza-texto); margin-top: 0.25rem; }
        .tabela { width: 100%; border-collapse: collapse; }
        .tabela th, .tabela td {
            text-align: left; padding: 0.65rem 0.5rem;
            border-bottom: 1px solid var(--cinza-borda); font-size: 0.9rem;
        }
        .tabela th { color: var(--cinza-texto); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.02em; }
        .selo { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .selo-sim { background-color: rgba(46,125,50,0.12); color: var(--verde-sucesso); }
        .selo-nao { background-color: rgba(74,85,92,0.12); color: var(--cinza-texto); }
        .acao-link { background: none; border: none; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: var(--vermelho-atrativo); padding: 0; }
        @media (max-width: 1023px) { .admin-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar-admin.php'; ?>

    <main class="conteudo">
        <h1 style="margin-bottom:1.25rem;">Refeições</h1>

        <?php if ($msg === 'cadastrada'): ?>
            <p class="mensagem-sucesso">Refeição cadastrada com sucesso.</p>
        <?php elseif ($msg === 'excluida'): ?>
            <p class="mensagem-sucesso">Refeição excluída.</p>
        <?php elseif ($msg === 'vinculada'): ?>
            <p class="mensagem-erro">Não é possível excluir: esta refeição está vinculada a uma ou mais enquetes.</p>
        <?php elseif ($msg === 'erro'): ?>
            <p class="mensagem-erro">Informe ao menos o título da refeição.</p>
        <?php endif; ?>

        <div class="admin-grid">
            <section class="cartao admin-form">
                <h2>Nova refeição</h2>
                <form method="post" action="refeicoes.php">
                    <input type="hidden" name="acao" value="cadastrar">
                    <div class="grupo-campo">
                        <label class="rotulo" for="titulo">Título</label>
                        <input class="campo" type="text" id="titulo" name="titulo" required placeholder="Ex.: Feijoada">
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="descricao">Descrição do cardápio</label>
                        <textarea class="area-texto" id="descricao" name="descricao" rows="3"
                                  placeholder="Ex.: Arroz, Feijão, Carne de Porco, Couve"></textarea>
                    </div>
                    <div class="grupo-campo">
                        <label class="rotulo" for="imagem">Imagem (.webp em /assets/img/refeicoes)</label>
                        <input class="campo" type="text" id="imagem" name="imagem" placeholder="Ex.: feijoada.webp">
                        <span class="dica">Apenas o nome do arquivo. Em branco usa a imagem padrão.</span>
                    </div>
                    <button type="submit" class="botao-primario">Cadastrar</button>
                </form>
            </section>

            <section class="cartao admin-lista">
                <h2>Refeições cadastradas</h2>
                <table class="tabela">
                    <thead>
                        <tr><th>Título</th><th>Descrição</th><th>Imagem</th><th>Ação</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$refeicoes): ?>
                            <tr><td colspan="4">Nenhuma refeição cadastrada.</td></tr>
                        <?php else: foreach ($refeicoes as $r):
                            $desc = (string) ($r['descricao'] ?? '');
                            $resumo = mb_strlen($desc) > 60 ? mb_substr($desc, 0, 60) . '…' : $desc; ?>
                            <tr>
                                <td><?= htmlspecialchars($r['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($resumo, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="selo <?= !empty($r['imagem']) ? 'selo-sim' : 'selo-nao' ?>">
                                        <?= !empty($r['imagem']) ? 'Sim' : 'Não' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="refeicoes.php" onsubmit="return confirm('Excluir esta refeição?');">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="refeicao_id" value="<?= (int) $r['id'] ?>">
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
