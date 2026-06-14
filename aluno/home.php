<?php
/**
 * aluno/home.php
 * Início do Aluno — exibe a refeição do dia (hero do prato do dia) e o
 * acesso à enquete, com contador regressivo até o horário limite.
 */

require_once __DIR__ . '/../includes/auth.php'; // sessão (antes de qualquer HTML)
require_once __DIR__ . '/../includes/db.php';   // $pdo

verificar_sessao('aluno');
$usuario = usuario_logado();

// Busca a refeição cadastrada para a data de hoje
$hoje = date('Y-m-d');
$stmt = $pdo->prepare(
    'SELECT id, titulo, descricao, imagem, data_refeicao, horario_limite
       FROM refeicoes
      WHERE data_refeicao = :hoje
      ORDER BY horario_limite DESC
      LIMIT 1'
);
$stmt->execute([':hoje' => $hoje]);
$refeicao = $stmt->fetch();

$aberta = $refeicao ? enquete_aberta($refeicao['horario_limite']) : false;

// Segundos restantes para o contador (calculado no servidor, sem AJAX)
$segundos_restantes = $aberta ? max(0, strtotime($refeicao['horario_limite']) - time()) : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Início · SIAetec</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* --- Estilos específicos da home do aluno --- */
        .hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            align-items: center;
        }
        .hero-rotulo {
            display: block;
            text-transform: uppercase;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.04em;
            color: var(--vermelho-institucional);
            margin-bottom: 0.5rem;
        }
        .hero-titulo { font-size: 2rem; margin-bottom: 0.75rem; }
        .hero-descricao { color: var(--cinza-texto); margin-bottom: 1rem; }
        .hero-chamada { color: var(--preto); margin-bottom: 1.25rem; }
        .hero-acao { max-width: 260px; }
        .hero-contador {
            margin-top: 0.75rem;
            font-size: 0.875rem;
            color: var(--cinza-texto);
        }
        .hero-imagem {
            width: 100%;
            height: 320px;
            object-fit: cover;
            border-radius: var(--raio-borda-grande);
        }
        .hero-vazio { text-align: center; color: var(--cinza-texto); padding: 1rem; }

        @media (max-width: 767px) {
            .hero { grid-template-columns: 1fr; gap: 1.25rem; }
            .hero-imagem { height: 220px; order: -1; }
            .hero-acao { max-width: 100%; }
            .hero-titulo { font-size: 1.6rem; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar-aluno.php'; ?>

    <main class="conteudo">
        <?php if (!$refeicao): ?>
            <section class="cartao">
                <p class="hero-vazio">Nenhuma refeição disponível no momento.</p>
            </section>
        <?php else: ?>
            <section class="cartao hero">
                <div class="hero-conteudo">
                    <span class="hero-rotulo">
                        Prato do dia · <?= htmlspecialchars(formatar_data($refeicao['data_refeicao'], 'd/m'), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <h1 class="hero-titulo"><?= htmlspecialchars($refeicao['titulo'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="hero-descricao"><?= nl2br(htmlspecialchars($refeicao['descricao'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>
                    <p class="hero-chamada">Registre sua intenção alimentar para ajudar a cozinha a planejar melhor.</p>

                    <?php if ($aberta): ?>
                        <a class="botao-primario hero-acao" href="enquete.php">ENQUETE &rarr;</a>
                        <p class="hero-contador">
                            Enquete encerra em <strong id="contador">--:--:--</strong>
                        </p>
                    <?php else: ?>
                        <button class="botao-primario hero-acao" type="button" disabled>Enquete encerrada</button>
                    <?php endif; ?>
                </div>

                <img class="hero-imagem"
                     src="../assets/img/<?= htmlspecialchars(!empty($refeicao['imagem']) ? $refeicao['imagem'] : 'prato-padrao.webp', ENT_QUOTES, 'UTF-8') ?>"
                     alt="Imagem da refeição: <?= htmlspecialchars($refeicao['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                     width="600" height="320">
            </section>
        <?php endif; ?>
    </main>

    <?php if ($aberta): ?>
    <script>
        (function () {
            let restante = <?= (int) $segundos_restantes ?>;
            const alvo = document.getElementById('contador');
            const botao = document.querySelector('.hero-acao');

            function doisDigitos(n) { return String(n).padStart(2, '0'); }

            function render() {
                if (restante <= 0) {
                    alvo.textContent = '00:00:00';
                    if (botao) {
                        botao.textContent = 'Enquete encerrada';
                        if (botao.tagName === 'A') {
                            botao.removeAttribute('href');
                            botao.style.pointerEvents = 'none';
                            botao.style.opacity = '0.6';
                        }
                    }
                    clearInterval(timer);
                    return;
                }
                const h = Math.floor(restante / 3600);
                const m = Math.floor((restante % 3600) / 60);
                const s = restante % 60;
                alvo.textContent = doisDigitos(h) + ':' + doisDigitos(m) + ':' + doisDigitos(s);
                restante--;
            }

            render();
            const timer = setInterval(render, 1000);
        })();
    </script>
    <?php endif; ?>
</body>
</html>
