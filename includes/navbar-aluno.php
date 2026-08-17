<?php
/**
 * includes/navbar-aluno.php
 * Barra de navegação reutilizável das páginas do Aluno.
 *
 * Deve ser incluída via include('../includes/navbar-aluno.php') a partir das
 * páginas em /aluno/, DEPOIS de auth.php (depende de usuario_logado()).
 * Componente autocontido: traz seu próprio <style> e <script>.
 */

$navbar_usuario = usuario_logado();
$pagina_atual   = basename($_SERVER['SCRIPT_NAME'] ?? '');

/** Retorna a classe 'ativo' quando o link corresponde à página atual. */
function nav_ativo(string $arquivo, string $atual): string
{
    return $arquivo === $atual ? ' ativo' : '';
}
?>
<style>
    /* --- Marca / logos --- */
    .nav-marca { display: flex; align-items: center; gap: 0.75rem; }
    .nav-logo  { height: 36px; width: auto; display: block; }
    .nav-divisor {
        width: 1px;
        height: 28px;
        background-color: var(--cinza-borda);
    }

    /* --- Links (desktop) --- */
    .nav-links { display: flex; align-items: center; gap: 1.5rem; }
    .nav-link {
        font-size: 0.95rem;
        font-weight: 500;
        color: var(--cinza-texto);
        padding: 0.25rem 0;
        border-bottom: 2px solid transparent;
        transition: color 0.2s ease, border-color 0.2s ease;
    }
    .nav-link:hover { color: var(--vermelho-institucional); }
    .nav-link.ativo {
        color: var(--vermelho-institucional);
        border-bottom-color: var(--vermelho-institucional);
    }

    /* --- Ações à direita --- */
    .nav-acoes { display: flex; align-items: center; gap: 0.75rem; }

    .nav-perfil { position: relative; }
    .nav-perfil-botao {
        width: 40px; height: 40px;
        border-radius: 50%;
        border: 1px solid var(--cinza-borda);
        background-color: var(--cinza-claro);
        color: var(--vermelho-institucional);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
    }
    .nav-dropdown {
        position: absolute;
        right: 0;
        top: 52px;
        min-width: 200px;
        background-color: var(--branco);
        border: 1px solid var(--cinza-borda);
        border-radius: var(--raio-borda-medio);
        box-shadow: var(--sombra-cartao);
        padding: 0.75rem;
        z-index: 110;
    }
    .nav-dropdown[hidden] { display: none; }
    .nav-dropdown-nome { font-weight: 600; color: var(--preto); font-size: 0.95rem; }
    .nav-dropdown-rm   { font-size: 0.8rem; color: var(--cinza-texto); margin-bottom: 0.5rem; }
    .nav-dropdown a {
        display: block;
        padding: 0.5rem 0;
        font-size: 0.9rem;
        color: var(--cinza-texto);
        border-top: 1px solid var(--cinza-borda);
    }
    .nav-dropdown a:hover { color: var(--vermelho-institucional); }
    .nav-dropdown .nav-sair { color: var(--vermelho-atrativo); }

    /* --- Hambúrguer (oculto no desktop) --- */
    .nav-hamburguer {
        display: none;
        width: 44px; height: 44px;
        background: none; border: none;
        font-size: 1.6rem; line-height: 1;
        color: var(--vermelho-institucional);
        cursor: pointer;
    }

    /* --- Drawer mobile + overlay --- */
    .nav-overlay {
        position: fixed; inset: 0;
        background-color: rgba(0, 0, 0, 0.45);
        z-index: 120;
    }
    .nav-overlay[hidden] { display: none; }
    .nav-drawer {
        position: fixed; top: 0; right: 0;
        height: 100%; width: 260px; max-width: 80%;
        background-color: var(--branco);
        box-shadow: var(--sombra-cartao);
        padding: 1.5rem 1.25rem;
        z-index: 130;
        display: flex; flex-direction: column; gap: 0.25rem;
    }
    .nav-drawer[hidden] { display: none; }
    .nav-drawer a {
        display: flex; align-items: center;
        min-height: 48px;
        font-size: 1rem; font-weight: 500;
        color: var(--cinza-texto);
        border-bottom: 1px solid var(--cinza-borda);
    }
    .nav-drawer a:hover { color: var(--vermelho-institucional); }
    .nav-drawer-fechar {
        align-self: flex-end;
        background: none; border: none;
        font-size: 1.8rem; line-height: 1;
        color: var(--cinza-texto);
        cursor: pointer;
        margin-bottom: 0.5rem;
    }

    @media (max-width: 767px) {
        .nav-links { display: none; }
        .nav-hamburguer { display: flex; align-items: center; justify-content: center; }
    }
</style>

<header class="barra-navegacao">
    <div class="nav-marca">
        
    <a href="home.php">
        <img class="nav-logo" src="../assets/img/siaetec-logo.webp" alt="SIAetec" width="120" height="36">
    </a>
        <span class="nav-divisor"></span>
        <a href="home.php">
            <img class="nav-logo" src="../assets/img/cps-etec-logo.webp" alt="Etec · Centro Paula Souza"
                 width="90" height="36" loading="lazy" decoding="async">
        </a>
    </div>

    <nav class="nav-links">
        <a class="nav-link<?= nav_ativo('home.php', $pagina_atual) ?>" href="home.php">Início</a>
        <a class="nav-link<?= nav_ativo('enquete.php', $pagina_atual) ?>" href="enquete.php">Enquete</a>
        <a class="nav-link<?= nav_ativo('sugestoes.php', $pagina_atual) ?>" href="sugestoes.php">Sugestões</a>
        <a class="nav-link<?= nav_ativo('suporte.php', $pagina_atual) ?>" href="suporte.php">Suporte</a>
        <a class="nav-link<?= nav_ativo('sobre.php', $pagina_atual) ?>" href="sobre.php">Sobre</a>
    </nav>

    <div class="nav-acoes">
        <button class="nav-hamburguer" id="nav-hamburguer" aria-label="Abrir menu de navegação">&#9776;</button>

        <div class="nav-perfil">
            <button class="nav-perfil-botao" id="nav-perfil-botao" aria-label="Abrir menu do perfil" aria-haspopup="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4.4 0-8 2.7-8 6v1h16v-1c0-3.3-3.6-6-8-6z"/>
                </svg>
            </button>
            <div class="nav-dropdown" id="nav-dropdown" hidden>
                <p class="nav-dropdown-nome"><?= htmlspecialchars($navbar_usuario['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="nav-dropdown-rm">RM: <?= htmlspecialchars($navbar_usuario['login'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <a href="perfil.php">Perfil</a>
                <a href="alterar-senha.php">Alterar senha</a>
                <a href="../logout.php" class="nav-sair">Sair</a>
            </div>
        </div>
    </div>
</header>

<!-- Drawer de navegação para mobile -->
<div class="nav-overlay" id="nav-overlay" hidden></div>
<aside class="nav-drawer" id="nav-drawer" hidden>
    <button class="nav-drawer-fechar" id="nav-drawer-fechar" aria-label="Fechar menu">&times;</button>
    <a href="home.php">Início</a>
    <a href="enquete.php">Enquete</a>
    <a href="sugestoes.php">Sugestões</a>
    <a href="suporte.php">Suporte</a>
    <a href="sobre.php">Sobre</a>
    <a href="../logout.php">Sair</a>
</aside>

<script>
    (function () {
        // Dropdown do perfil
        const perfilBotao = document.getElementById('nav-perfil-botao');
        const dropdown    = document.getElementById('nav-dropdown');
        perfilBotao.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.hidden = !dropdown.hidden;
        });
        document.addEventListener('click', function (e) {
            if (!dropdown.hidden && !dropdown.contains(e.target)) {
                dropdown.hidden = true;
            }
        });

        // Drawer mobile
        const hamburguer = document.getElementById('nav-hamburguer');
        const overlay    = document.getElementById('nav-overlay');
        const drawer     = document.getElementById('nav-drawer');
        const fechar     = document.getElementById('nav-drawer-fechar');

        function abrirDrawer(aberto) {
            overlay.hidden = !aberto;
            drawer.hidden  = !aberto;
        }
        hamburguer.addEventListener('click', () => abrirDrawer(true));
        fechar.addEventListener('click', () => abrirDrawer(false));
        overlay.addEventListener('click', () => abrirDrawer(false));
    })();
</script>
