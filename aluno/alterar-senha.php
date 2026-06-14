<?php
/**
 * aluno/alterar-senha.php
 * Tela de criação de senha — obrigatória no primeiro acesso do aluno.
 *
 * Recebe a nova senha via POST, grava com password_hash() e marca
 * senha_alterada = 1. Também serve para o aluno trocar a senha depois.
 */

require_once __DIR__ . '/../includes/auth.php'; // sessão (no topo, antes de qualquer HTML)
require_once __DIR__ . '/../includes/db.php';   // $pdo

// Exige sessão de aluno. Por estar nesta própria página, a verificação
// NÃO redireciona quando senha_alterada = 0 (é exatamente o objetivo daqui).
verificar_sessao('aluno');

$usuario = usuario_logado();
$erro    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova     = $_POST['nova_senha'] ?? '';
    $confirma = $_POST['confirmar_senha'] ?? '';

    if (strlen($nova) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($nova !== $confirma) {
        $erro = 'As senhas não coincidem.';
    } else {
        $hash = password_hash($nova, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE usuarios SET senha = :senha, senha_alterada = 1 WHERE id = :id');
        $stmt->execute([':senha' => $hash, ':id' => $usuario['usuario_id']]);

        $_SESSION['senha_alterada'] = 1;
        redirecionar('home.php');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Crie sua senha · SIAetec</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* --- Estilos específicos da tela de criação de senha --- */
        .tela-senha {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background-color: var(--cinza-claro);
        }
        .senha-cartao { max-width: 420px; }
        .senha-cartao h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .senha-subtitulo {
            font-size: 0.875rem;
            color: var(--cinza-texto);
            margin-bottom: 1.5rem;
        }
        .grupo-campo { margin-bottom: 1rem; }

        .forca-senha { margin-top: 0.5rem; }
        .forca-barra {
            height: 6px;
            border-radius: 3px;
            background-color: var(--cinza-borda);
            overflow: hidden;
        }
        .forca-barra span {
            display: block;
            height: 100%;
            width: 0;
            transition: width 0.2s ease, background-color 0.2s ease;
        }
        .forca-texto {
            display: inline-block;
            margin-top: 0.35rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .forca-fraca  { background-color: var(--vermelho-atrativo); }
        .forca-media  { background-color: var(--amarelo-atencao); }
        .forca-forte  { background-color: var(--verde-sucesso); }
        .texto-fraca  { color: var(--vermelho-atrativo); }
        .texto-media  { color: var(--amarelo-atencao); }
        .texto-forte  { color: var(--verde-sucesso); }
    </style>
</head>
<body class="tela-senha">
    <main>
        <section class="cartao senha-cartao">
            <h1>Crie sua senha</h1>
            <p class="senha-subtitulo">Este é seu primeiro acesso. Defina uma senha pessoal para continuar.</p>

            <form method="post" action="alterar-senha.php" id="form-senha" novalidate>
                <div class="grupo-campo">
                    <label class="rotulo" for="nova">Nova senha</label>
                    <input class="campo" type="password" id="nova" name="nova_senha" required minlength="6" autocomplete="new-password">
                    <div class="forca-senha" aria-live="polite">
                        <div class="forca-barra"><span id="forca-preenchimento"></span></div>
                        <span id="forca-texto" class="forca-texto"></span>
                    </div>
                </div>

                <div class="grupo-campo">
                    <label class="rotulo" for="confirma">Confirmar nova senha</label>
                    <input class="campo" type="password" id="confirma" name="confirmar_senha" required autocomplete="new-password">
                </div>

                <p class="mensagem-erro" id="erro-coincide" hidden>As senhas não coincidem.</p>

                <button type="submit" class="botao-primario">Confirmar</button>

                <?php if ($erro !== ''): ?>
                    <p class="mensagem-erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </form>
        </section>
    </main>

    <script>
        (function () {
            const nova        = document.getElementById('nova');
            const confirma    = document.getElementById('confirma');
            const barra       = document.getElementById('forca-preenchimento');
            const texto       = document.getElementById('forca-texto');
            const erroCoincide = document.getElementById('erro-coincide');
            const form        = document.getElementById('form-senha');

            // Avalia a força da senha (comprimento + variedade de caracteres)
            function avaliarForca(senha) {
                let pontos = 0;
                if (senha.length >= 6)            pontos++;
                if (senha.length >= 10)           pontos++;
                if (/[A-Z]/.test(senha))          pontos++;
                if (/[0-9]/.test(senha))          pontos++;
                if (/[^A-Za-z0-9]/.test(senha))   pontos++;
                if (pontos <= 2) return { nivel: 'fraca',  largura: '33%' };
                if (pontos <= 3) return { nivel: 'media',  largura: '66%' };
                return { nivel: 'forte', largura: '100%' };
            }

            nova.addEventListener('input', function () {
                if (nova.value === '') {
                    barra.style.width = '0';
                    texto.textContent = '';
                    return;
                }
                const r = avaliarForca(nova.value);
                barra.style.width = r.largura;
                barra.className = 'forca-' + r.nivel;
                texto.className = 'forca-texto texto-' + r.nivel;
                texto.textContent = 'Senha ' + r.nivel;
            });

            // Validação client-side: senhas devem coincidir antes do envio
            form.addEventListener('submit', function (e) {
                if (nova.value !== confirma.value) {
                    e.preventDefault();
                    erroCoincide.hidden = false;
                } else {
                    erroCoincide.hidden = true;
                }
            });
        })();
    </script>
</body>
</html>
