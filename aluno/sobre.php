<?php
/**
 * aluno/sobre.php
 * Página institucional "Sobre o SIAetec" (ui-design §9) — conteúdo estático.
 */

require_once __DIR__ . '/../includes/auth.php'; // sessão (antes de qualquer HTML)

verificar_sessao('aluno');

/*
 * TODO (equipe): substituir os integrantes abaixo pelos nomes e papéis reais.
 */
$integrantes = [
    ['nome' => 'Integrante 1', 'papel' => 'Backend'],
    ['nome' => 'Integrante 2', 'papel' => 'Frontend'],
    ['nome' => 'Integrante 3', 'papel' => 'Banco de Dados'],
    ['nome' => 'Integrante 4', 'papel' => 'Documentação'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Sobre · SIAetec</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* --- Estilos específicos da página sobre --- */
        .sobre-cartao { max-width: 760px; margin: 0 auto; }
        .sobre-cartao h1 { margin-bottom: 0.75rem; }
        .sobre-texto { color: var(--cinza-texto); margin-bottom: 1.5rem; }
        .sobre-subtitulo { font-size: 1.1rem; margin-bottom: 1rem; }
        .sobre-integrantes {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
        .sobre-integrante {
            border: 1px solid var(--cinza-borda);
            border-radius: var(--raio-borda-pequeno);
            padding: 0.75rem 1rem;
        }
        .sobre-integrante strong { display: block; color: var(--preto); }
        .sobre-integrante span { font-size: 0.85rem; color: var(--cinza-texto); }
        .sobre-logos {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--cinza-borda);
        }
        .sobre-logos img { height: 44px; width: auto; }

        @media (max-width: 767px) {
            .sobre-integrantes { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar-aluno.php'; ?>

    <main class="conteudo">
        <section class="cartao sobre-cartao">
            <h1>Sobre o SIAetec</h1>
            <p class="sobre-texto">
                O SIAetec é um Sistema de Intenção Alimentar Escolar desenvolvido como Trabalho de
                Conclusão de Curso. Seu objetivo é permitir que os alunos informem, antes do horário
                limite, se pretendem consumir a refeição do dia, ajudando a escola a planejar melhor
                a produção da merenda e a reduzir o desperdício de alimentos.
            </p>

            <h2 class="sobre-subtitulo">Desenvolvido por</h2>
            <div class="sobre-integrantes">
                <?php foreach ($integrantes as $i): ?>
                    <div class="sobre-integrante">
                        <strong><?= htmlspecialchars($i['nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars($i['papel'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="sobre-logos">
                <img src="../assets/img/siaetec-logo.webp" alt="SIAetec" height="44" loading="lazy" decoding="async">
                <img src="../assets/img/cps-etec-logo.webp" alt="Etec · Centro Paula Souza" height="44" loading="lazy" decoding="async">
            </div>
        </section>
    </main>
</body>
</html>
