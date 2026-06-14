<?php
/**
 * index.php — Ponto de entrada do sistema (tela de login).
 *
 * Recebe RM/Usuário + Senha via POST, valida com password_verify(),
 * inicia a sessão e redireciona para a área correta do usuário.
 * Página pré-login: não possui navbar.
 */

require_once __DIR__ . '/includes/auth.php';   // inicia a sessão
require_once __DIR__ . '/includes/db.php';      // disponibiliza $pdo

// Usuário já autenticado não vê o login: vai direto para sua área
$sessao = usuario_logado();
if ($sessao !== null) {
    if ($sessao['tipo'] === 'admin') {
        redirecionar('admin/dashboard.php');
    }
    if ($sessao['senha_alterada'] === 0) {
        redirecionar('aluno/alterar-senha.php');
    }
    redirecionar('aluno/home.php');
}

$erro = '';
$perfil_ativo = 'aluno';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $perfil       = (($_POST['perfil'] ?? '') === 'admin') ? 'admin' : 'aluno';
    $perfil_ativo = $perfil;
    $login        = trim($_POST['login'] ?? '');
    $senha        = $_POST['senha'] ?? '';

    if ($login === '' || $senha === '') {
        $erro = 'Preencha todos os campos.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, nome, login, senha, tipo, senha_alterada
               FROM usuarios
              WHERE login = :login AND tipo = :tipo AND ativo = 1
              LIMIT 1'
        );
        $stmt->execute([':login' => $login, ':tipo' => $perfil]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Credenciais válidas — renova o id de sessão (previne fixation)
            session_regenerate_id(true);
            $_SESSION['usuario_id']     = (int) $usuario['id'];
            $_SESSION['tipo']           = $usuario['tipo'];
            $_SESSION['nome']           = $usuario['nome'];
            $_SESSION['login']          = $usuario['login'];
            $_SESSION['senha_alterada'] = (int) $usuario['senha_alterada'];

            if ($usuario['tipo'] === 'aluno') {
                // Recupera o id escolar do aluno para uso nas páginas internas
                $stmt_aluno = $pdo->prepare('SELECT id FROM alunos WHERE usuario_id = :uid LIMIT 1');
                $stmt_aluno->execute([':uid' => $usuario['id']]);
                $aluno = $stmt_aluno->fetch();
                $_SESSION['aluno_id'] = $aluno ? (int) $aluno['id'] : null;

                if ((int) $usuario['senha_alterada'] === 0) {
                    redirecionar('aluno/alterar-senha.php');
                }
                redirecionar('aluno/home.php');
            }

            // Admin
            redirecionar('admin/dashboard.php');
        }

        $erro = 'Credenciais inválidas. Verifique os dados e tente novamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Entrar · SIAetec</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* --- Estilos específicos da tela de login --- */
        .tela-login {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background-color: var(--cinza-claro);
        }
        .login-cartao { max-width: 420px; }
        .login-logo {
            display: block;
            height: 48px;
            width: auto;
            margin: 0 auto 1.25rem;
        }
        .login-abas {
            display: flex;
            gap: 0.5rem;
            border-bottom: 1px solid var(--cinza-borda);
            margin-bottom: 1.25rem;
        }
        .login-aba {
            flex: 1;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 0.75rem 0;
            font-family: var(--fonte-principal);
            font-size: 1rem;
            font-weight: 600;
            color: var(--cinza-texto);
            cursor: pointer;
            transition: color 0.2s ease, border-color 0.2s ease;
        }
        .login-aba.ativa {
            color: var(--vermelho-institucional);
            border-bottom-color: var(--vermelho-atrativo);
        }
        .grupo-campo { margin-bottom: 1rem; }
        .oculto { display: none; }
    </style>
</head>
<body class="tela-login">
    <main class="login-container">
        <section class="cartao login-cartao">
            <img class="login-logo" src="assets/img/siaetec-logo.webp" alt="SIAetec" width="160" height="48">

            <div class="login-abas" role="tablist">
                <button type="button" class="login-aba ativa" data-perfil="aluno">Aluno</button>
                <button type="button" class="login-aba" data-perfil="admin">Admin</button>
            </div>

            <!-- Formulário do Aluno -->
            <form method="post" action="index.php" class="login-form" data-form="aluno">
                <input type="hidden" name="perfil" value="aluno">
                <div class="grupo-campo">
                    <label class="rotulo" for="rm-aluno">RM</label>
                    <input class="campo" type="text" id="rm-aluno" name="login" required autocomplete="username">
                </div>
                <div class="grupo-campo">
                    <label class="rotulo" for="senha-aluno">Senha</label>
                    <input class="campo" type="password" id="senha-aluno" name="senha" required autocomplete="current-password">
                </div>
                <button type="submit" class="botao-primario">Entrar</button>
                <?php if ($erro !== '' && $perfil_ativo === 'aluno'): ?>
                    <p class="mensagem-erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </form>

            <!-- Formulário do Admin -->
            <form method="post" action="index.php" class="login-form oculto" data-form="admin">
                <input type="hidden" name="perfil" value="admin">
                <div class="grupo-campo">
                    <label class="rotulo" for="user-admin">Usuário</label>
                    <input class="campo" type="text" id="user-admin" name="login" required autocomplete="username">
                </div>
                <div class="grupo-campo">
                    <label class="rotulo" for="senha-admin">Senha</label>
                    <input class="campo" type="password" id="senha-admin" name="senha" required autocomplete="current-password">
                </div>
                <button type="submit" class="botao-primario">Entrar</button>
                <?php if ($erro !== '' && $perfil_ativo === 'admin'): ?>
                    <p class="mensagem-erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </form>
        </section>
    </main>

    <script>
        (function () {
            const abas  = document.querySelectorAll('.login-aba');
            const forms = document.querySelectorAll('.login-form');

            function ativar(perfil) {
                abas.forEach(a => a.classList.toggle('ativa', a.dataset.perfil === perfil));
                forms.forEach(f => f.classList.toggle('oculto', f.dataset.form !== perfil));
            }

            abas.forEach(aba => aba.addEventListener('click', () => ativar(aba.dataset.perfil)));

            // Mantém aberta a aba que tentou autenticar (em caso de erro)
            ativar(<?= json_encode($perfil_ativo) ?>);
        })();
    </script>
</body>
</html>
