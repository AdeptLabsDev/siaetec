<?php
/**
 * admin/dashboard.php
 * Painel do Admin — cartões de resumo e gráfico histórico de votos.
 */

require_once __DIR__ . '/../includes/auth.php'; // sessão (antes de qualquer HTML)
require_once __DIR__ . '/../includes/db.php';   // $pdo

verificar_sessao('admin');
$usuario = usuario_logado();

// Cartões de resumo
$total_alunos    = (int) $pdo->query('SELECT COUNT(*) FROM alunos WHERE ativo = 1')->fetchColumn();
$total_refeicoes = (int) $pdo->query('SELECT COUNT(*) FROM refeicoes')->fetchColumn();

$hoje = date('Y-m-d');
$stmt = $pdo->prepare(
    'SELECT COUNT(*)
       FROM intencoes_alimentares i
       JOIN refeicoes r ON r.id = i.refeicao_id
      WHERE r.data_refeicao = :hoje'
);
$stmt->execute([':hoje' => $hoje]);
$respostas_hoje = (int) $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard · SIAetec Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* --- Estilos específicos do dashboard --- */
        .dash-titulo { margin-bottom: 1.25rem; }
        .dash-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .dash-card { text-align: left; }
        .dash-card .numero { font-size: 2.25rem; font-weight: 700; color: var(--vermelho-institucional); }
        .dash-card .rotulo { font-size: 0.9rem; color: var(--cinza-texto); }
        .grafico-wrapper { min-height: 320px; }
        .grafico-wrapper h2 { font-size: 1.25rem; margin-bottom: 1rem; }

        @media (max-width: 767px) {
            .dash-cards { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar-admin.php'; ?>

    <main class="conteudo">
        <h1 class="dash-titulo">Dashboard</h1>

        <div class="dash-cards">
            <div class="cartao dash-card">
                <div class="numero"><?= $total_alunos ?></div>
                <div class="rotulo">Alunos ativos</div>
            </div>
            <div class="cartao dash-card">
                <div class="numero"><?= $total_refeicoes ?></div>
                <div class="rotulo">Refeições cadastradas</div>
            </div>
            <div class="cartao dash-card">
                <div class="numero"><?= $respostas_hoje ?></div>
                <div class="rotulo">Respostas hoje</div>
            </div>
        </div>

        <section class="cartao grafico-wrapper">
            <h2>Histórico de votos por data</h2>
            <canvas id="grafico-historico" data-endpoint="../api/grafico.php" height="120"></canvas>
        </section>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js" defer></script>
    <script src="../assets/js/grafico.js" defer></script>
</body>
</html>
