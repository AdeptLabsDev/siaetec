<?php
/**
 * admin/enquete.php
 * Resultados em tempo real da enquete do dia. Os totais são renderizados no
 * servidor e atualizados via fetch para api/resultados.php a cada 30s.
 */

require_once __DIR__ . '/../includes/auth.php'; // sessão (antes de qualquer HTML)
require_once __DIR__ . '/../includes/db.php';   // $pdo

verificar_sessao('admin');
$usuario = usuario_logado();

// Enquete a exibir: ?id tem prioridade; sem ele, busca a enquete de hoje
$id_param = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id_param) {
    $stmt = $pdo->prepare(
        'SELECT e.id AS enquete_id, e.data_enquete, e.horario_limite, r.titulo
           FROM enquetes e
           JOIN refeicoes r ON r.id = e.refeicao_id
          WHERE e.id = :id LIMIT 1'
    );
    $stmt->execute([':id' => $id_param]);
} else {
    $stmt = $pdo->prepare(
        'SELECT e.id AS enquete_id, e.data_enquete, e.horario_limite, r.titulo
           FROM enquetes e
           JOIN refeicoes r ON r.id = e.refeicao_id
          WHERE e.data_enquete = :hoje LIMIT 1'
    );
    $stmt->execute([':hoje' => date('Y-m-d')]);
}
$enquete = $stmt->fetch();

$sim = $nao = $sem_resposta = $total_alunos = $responderam = 0;
$aberta = false;
if ($enquete) {
    $total_alunos = (int) $pdo->query('SELECT COUNT(*) FROM alunos WHERE ativo = 1')->fetchColumn();
    $st = $pdo->prepare(
        "SELECT COALESCE(SUM(resposta='sim'),0) AS sim, COALESCE(SUM(resposta='nao'),0) AS nao
           FROM intencoes_alimentares WHERE enquete_id = :e"
    );
    $st->execute([':e' => $enquete['enquete_id']]);
    $row = $st->fetch();
    $sim = (int) $row['sim'];
    $nao = (int) $row['nao'];
    $responderam  = $sim + $nao;
    $sem_resposta = max(0, $total_alunos - $responderam);
    $aberta = enquete_aberta($enquete['horario_limite']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Enquete · SIAetec Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .enquete-cabecalho { display: flex; align-items: baseline; gap: 0.75rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
        .selo { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .selo-aberta    { background-color: rgba(46,125,50,0.12); color: var(--verde-sucesso); }
        .selo-encerrada { background-color: rgba(74,85,92,0.12); color: var(--cinza-texto); }
        .resultados { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
        .resultado-card { text-align: center; }
        .resultado-card .numero { font-size: 2.25rem; font-weight: 700; }
        .resultado-card .rotulo { font-size: 0.85rem; color: var(--cinza-texto); }
        .num-sim { color: var(--verde-sucesso); }
        .num-nao { color: var(--vermelho-atrativo); }
        .num-sem { color: var(--cinza-texto); }
        .num-tot { color: var(--vermelho-institucional); }
        .atualizado { font-size: 0.8rem; color: var(--cinza-texto); margin-top: 1rem; }
        @media (max-width: 767px) { .resultados { grid-template-columns: 1fr 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar-admin.php'; ?>

    <main class="conteudo">
        <?php if (!$enquete): ?>
            <section class="cartao">
                <p>Nenhuma enquete cadastrada para hoje.</p>
            </section>
        <?php else: ?>
            <div class="enquete-cabecalho">
                <h1><?= htmlspecialchars($enquete['titulo'], ENT_QUOTES, 'UTF-8') ?></h1>
                <span class="selo <?= $aberta ? 'selo-aberta' : 'selo-encerrada' ?>">
                    <?= $aberta ? 'Enquete aberta' : 'Enquete encerrada' ?>
                </span>
            </div>

            <section class="cartao" data-enquete-id="<?= (int) $enquete['enquete_id'] ?>">
                <div class="resultados">
                    <div class="resultado-card">
                        <div class="numero num-sim" id="r-sim"><?= $sim ?></div>
                        <div class="rotulo">Sim</div>
                    </div>
                    <div class="resultado-card">
                        <div class="numero num-nao" id="r-nao"><?= $nao ?></div>
                        <div class="rotulo">Não</div>
                    </div>
                    <div class="resultado-card">
                        <div class="numero num-sem" id="r-sem"><?= $sem_resposta ?></div>
                        <div class="rotulo">Sem resposta</div>
                    </div>
                    <div class="resultado-card">
                        <div class="numero num-tot" id="r-tot"><?= $total_alunos ?></div>
                        <div class="rotulo">Alunos ativos</div>
                    </div>
                </div>
                <p class="atualizado">Atualizado às <span id="hora-atualizacao"><?= date('H:i:s') ?></span> · atualização automática a cada 30s.</p>
            </section>
        <?php endif; ?>
    </main>

    <?php if ($enquete): ?>
    <script>
        (function () {
            const cartao = document.querySelector('[data-enquete-id]');
            const id = cartao.dataset.enqueteId;
            const elSim = document.getElementById('r-sim');
            const elNao = document.getElementById('r-nao');
            const elSem = document.getElementById('r-sem');
            const elTot = document.getElementById('r-tot');
            const elHora = document.getElementById('hora-atualizacao');

            async function atualizar() {
                try {
                    const resp = await fetch('../api/resultados.php?enquete_id=' + encodeURIComponent(id));
                    const d = await resp.json();
                    if (!resp.ok || !d || d.enquete === null) return;
                    elSim.textContent = d.sim;
                    elNao.textContent = d.nao;
                    elSem.textContent = d.sem_resposta;
                    elTot.textContent = d.total_alunos;
                    elHora.textContent = new Date().toLocaleTimeString('pt-BR');
                } catch (e) {
                    // Mantém os últimos valores em caso de falha momentânea
                }
            }

            setInterval(atualizar, 30000);
        })();
    </script>
    <?php endif; ?>
</body>
</html>
