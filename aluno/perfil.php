<?php
/**
 * aluno/perfil.php
 * Perfil do aluno (ui-design §10) — dados escolares em modo somente leitura.
 */

require_once __DIR__ . '/../includes/auth.php'; // sessão (antes de qualquer HTML)
require_once __DIR__ . '/../includes/db.php';   // $pdo

verificar_sessao('aluno');
$usuario = usuario_logado();

// Dados escolares: turma, curso e ano letivo
$dados_aluno = null;
if ($usuario['aluno_id'] !== null) {
    $stmt = $pdo->prepare(
        'SELECT t.nome AS turma, t.curso AS curso, a.ano_letivo AS ano_letivo
           FROM alunos a
           JOIN turmas t ON t.id = a.turma_id
          WHERE a.id = :id
          LIMIT 1'
    );
    $stmt->execute([':id' => $usuario['aluno_id']]);
    $dados_aluno = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Perfil · SIAetec</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* --- Estilos específicos da página de perfil --- */
        .perfil-cartao { max-width: 420px; margin: 0 auto; text-align: center; }
        .perfil-avatar {
            width: 88px; height: 88px;
            border-radius: 50%;
            background-color: var(--cinza-claro);
            color: var(--vermelho-institucional);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
        }
        .perfil-dados { text-align: left; margin-bottom: 1.5rem; }
        .perfil-linha {
            display: flex; justify-content: space-between; gap: 1rem;
            padding: 0.65rem 0;
            border-bottom: 1px solid var(--cinza-borda);
        }
        .perfil-linha:last-child { border-bottom: none; }
        .perfil-rotulo { font-size: 0.8rem; color: var(--cinza-texto); text-transform: uppercase; letter-spacing: 0.02em; }
        .perfil-valor { font-weight: 600; color: var(--preto); text-align: right; }
        .perfil-acoes { display: flex; flex-direction: column; gap: 0.75rem; }
        .perfil-sair {
            display: flex; align-items: center; justify-content: center; gap: 0.4rem;
            color: var(--cinza-texto);
            font-weight: 600;
            padding: 0.5rem;
        }
        .perfil-sair:hover { color: var(--vermelho-atrativo); }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar-aluno.php'; ?>

    <main class="conteudo">
        <section class="cartao perfil-cartao">
            <div class="perfil-avatar">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4.4 0-8 2.7-8 6v1h16v-1c0-3.3-3.6-6-8-6z"/>
                </svg>
            </div>

            <div class="perfil-dados">
                <div class="perfil-linha">
                    <span class="perfil-rotulo">Nome</span>
                    <span class="perfil-valor"><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="perfil-linha">
                    <span class="perfil-rotulo">RM</span>
                    <span class="perfil-valor"><?= htmlspecialchars($usuario['login'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="perfil-linha">
                    <span class="perfil-rotulo">Turma</span>
                    <span class="perfil-valor"><?= htmlspecialchars($dados_aluno['turma'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="perfil-linha">
                    <span class="perfil-rotulo">Curso</span>
                    <span class="perfil-valor"><?= htmlspecialchars($dados_aluno['curso'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="perfil-linha">
                    <span class="perfil-rotulo">Ano letivo</span>
                    <span class="perfil-valor"><?= htmlspecialchars((string) ($dados_aluno['ano_letivo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <div class="perfil-acoes">
                <a class="botao-secundario" href="alterar-senha.php">Alterar Senha</a>
                <a class="perfil-sair" href="../logout.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M16 13v-2H7V8l-5 4 5 4v-3h9zm3-10H11a2 2 0 00-2 2v3h2V5h8v14h-8v-3H9v3a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2z"/>
                    </svg>
                    Sair
                </a>
            </div>
        </section>
    </main>
</body>
</html>
