<?php
/**
 * admin/resumo.php
 * Resumo da refeição do dia (cardápio + totais) com envio à cozinha via
 * WhatsApp (Evolution API) e histórico dos envios anteriores.
 */

require_once __DIR__ . '/../includes/auth.php'; // sessão (antes de qualquer HTML)
require_once __DIR__ . '/../includes/db.php';   // $pdo

verificar_sessao('admin');
$usuario = usuario_logado();

// Refeição de hoje
$stmt = $pdo->prepare(
    'SELECT id, titulo, descricao, data_refeicao, horario_limite
       FROM refeicoes WHERE data_refeicao = :hoje
      ORDER BY horario_limite DESC LIMIT 1'
);
$stmt->execute([':hoje' => date('Y-m-d')]);
$refeicao = $stmt->fetch();

$sim = $nao = $sem_resposta = $total_alunos = $responderam = 0;
if ($refeicao) {
    $total_alunos = (int) $pdo->query('SELECT COUNT(*) FROM alunos WHERE ativo = 1')->fetchColumn();
    $st = $pdo->prepare(
        "SELECT COALESCE(SUM(resposta='sim'),0) AS sim, COALESCE(SUM(resposta='nao'),0) AS nao
           FROM intencoes_alimentares WHERE refeicao_id = :r"
    );
    $st->execute([':r' => $refeicao['id']]);
    $row = $st->fetch();
    $sim = (int) $row['sim'];
    $nao = (int) $row['nao'];
    $responderam  = $sim + $nao;
    $sem_resposta = max(0, $total_alunos - $responderam);
}

// Histórico de envios
$historico = $pdo->query(
    'SELECT re.enviado_em, re.destinatario, re.status, r.titulo
       FROM resumos_envio re
       JOIN refeicoes r ON r.id = re.refeicao_id
      ORDER BY re.enviado_em DESC
      LIMIT 20'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Resumo · SIAetec Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .resumo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start; }
        .resumo-linha { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--cinza-borda); }
        .resumo-linha:last-child { border-bottom: none; }
        .resumo-rotulo { color: var(--cinza-texto); }
        .resumo-valor { font-weight: 600; color: var(--preto); }
        .botao-enviar { margin-top: 1.25rem; max-width: 280px; }
        .tabela { width: 100%; border-collapse: collapse; }
        .tabela th, .tabela td { text-align: left; padding: 0.55rem 0.5rem; border-bottom: 1px solid var(--cinza-borda); font-size: 0.85rem; }
        .tabela th { color: var(--cinza-texto); text-transform: uppercase; font-size: 0.72rem; }
        .selo { display: inline-block; padding: 0.1rem 0.55rem; border-radius: 999px; font-size: 0.72rem; font-weight: 600; }
        .selo-enviado { background-color: rgba(46,125,50,0.12); color: var(--verde-sucesso); }
        .selo-erro    { background-color: rgba(235,0,51,0.12); color: var(--vermelho-atrativo); }
        @media (max-width: 1023px) { .resumo-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar-admin.php'; ?>

    <main class="conteudo">
        <h1 style="margin-bottom:1.25rem;">Resumo para a cozinha</h1>

        <div class="resumo-grid">
            <section class="cartao">
                <h2 style="font-size:1.25rem;margin-bottom:1rem;">Refeição de hoje</h2>
                <?php if (!$refeicao): ?>
                    <p>Nenhuma refeição cadastrada para hoje.</p>
                <?php else: ?>
                    <div class="resumo-linha"><span class="resumo-rotulo">Data</span><span class="resumo-valor"><?= htmlspecialchars(formatar_data($refeicao['data_refeicao'], 'd/m/Y'), ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div class="resumo-linha"><span class="resumo-rotulo">Cardápio</span><span class="resumo-valor"><?= htmlspecialchars($refeicao['titulo'], ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div class="resumo-linha"><span class="resumo-rotulo">Alunos ativos</span><span class="resumo-valor"><?= $total_alunos ?></span></div>
                    <div class="resumo-linha"><span class="resumo-rotulo">Responderam</span><span class="resumo-valor"><?= $responderam ?></span></div>
                    <div class="resumo-linha"><span class="resumo-rotulo">SIM</span><span class="resumo-valor"><?= $sim ?></span></div>
                    <div class="resumo-linha"><span class="resumo-rotulo">NÃO</span><span class="resumo-valor"><?= $nao ?></span></div>
                    <div class="resumo-linha"><span class="resumo-rotulo">Sem resposta</span><span class="resumo-valor"><?= $sem_resposta ?></span></div>

                    <button type="button" class="botao-primario botao-enviar" id="btn-enviar"
                            data-refeicao-id="<?= (int) $refeicao['id'] ?>">Enviar para a cozinha</button>
                    <p class="mensagem-sucesso" id="envio-sucesso" hidden>Resumo enviado com sucesso!</p>
                    <p class="mensagem-erro" id="envio-erro" hidden></p>
                <?php endif; ?>
            </section>

            <section class="cartao">
                <h2 style="font-size:1.25rem;margin-bottom:1rem;">Envios anteriores</h2>
                <table class="tabela">
                    <thead>
                        <tr><th>Data/Hora</th><th>Refeição</th><th>Destino</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$historico): ?>
                            <tr><td colspan="4">Nenhum envio registrado.</td></tr>
                        <?php else: foreach ($historico as $h): ?>
                            <tr>
                                <td><?= htmlspecialchars(formatar_data($h['enviado_em'], 'd/m/Y H:i'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($h['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($h['destinatario'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="selo <?= $h['status'] === 'enviado' ? 'selo-enviado' : 'selo-erro' ?>"><?= htmlspecialchars(ucfirst($h['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>

    <?php if ($refeicao): ?>
    <script>
        (function () {
            const botao   = document.getElementById('btn-enviar');
            const sucesso = document.getElementById('envio-sucesso');
            const erro    = document.getElementById('envio-erro');

            botao.addEventListener('click', async function () {
                sucesso.hidden = true;
                erro.hidden = true;
                botao.disabled = true;
                try {
                    const resp = await fetch('../api/enviar-resumo.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ refeicao_id: parseInt(botao.dataset.refeicaoId, 10) })
                    });
                    const d = await resp.json();
                    if (!resp.ok || d.erro) {
                        throw new Error(d.erro || 'Falha no envio.');
                    }
                    sucesso.hidden = false;
                    setTimeout(function () { location.reload(); }, 1200);
                } catch (e) {
                    erro.textContent = e.message;
                    erro.hidden = false;
                } finally {
                    botao.disabled = false;
                }
            });
        })();
    </script>
    <?php endif; ?>
</body>
</html>
