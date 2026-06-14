<?php
/**
 * aluno/suporte.php
 * Página de suporte do aluno (ui-design §8) — conteúdo estático, sem formulário.
 */

require_once __DIR__ . '/../includes/auth.php'; // sessão (antes de qualquer HTML)

verificar_sessao('aluno');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Suporte · SIAetec</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* --- Estilos específicos da página de suporte --- */
        .suporte-titulo { margin-bottom: 0.5rem; }
        .suporte-intro { color: var(--cinza-texto); margin-bottom: 1.5rem; max-width: 640px; }
        .suporte-cartoes {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
        }
        .suporte-item { display: flex; gap: 0.85rem; align-items: flex-start; }
        .suporte-icone { color: var(--vermelho-institucional); flex-shrink: 0; }
        .suporte-item h3 { font-size: 1rem; margin-bottom: 0.2rem; }
        .suporte-item p { font-size: 0.9rem; color: var(--cinza-texto); }

        @media (max-width: 767px) {
            .suporte-cartoes { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar-aluno.php'; ?>

    <main class="conteudo">
        <h1 class="suporte-titulo">Suporte</h1>
        <p class="suporte-intro">
            Precisa de ajuda com o sistema ou com a enquete da merenda? Use os contatos abaixo
            para falar com a equipe responsável.
        </p>

        <div class="suporte-cartoes">
            <div class="cartao suporte-item">
                <svg class="suporte-icone" width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
                <div>
                    <h3>E-mail</h3>
                    <p>contato@etec.sp.gov.br</p>
                </div>
            </div>

            <div class="cartao suporte-item">
                <svg class="suporte-icone" width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 11h-4V7h2v4h2v2z"/>
                </svg>
                <div>
                    <h3>Atendimento</h3>
                    <p>Segunda a sexta, das 8h às 17h</p>
                </div>
            </div>

            <div class="cartao suporte-item">
                <svg class="suporte-icone" width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4.4 0-8 2.7-8 6v1h16v-1c0-3.3-3.6-6-8-6z"/>
                </svg>
                <div>
                    <h3>Responsável</h3>
                    <p>Equipe do TCC SIAetec</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
