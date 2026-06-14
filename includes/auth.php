<?php
/**
 * includes/auth.php
 * Sessão centralizada, verificação de acesso e dados do usuário logado.
 *
 * Deve ser incluído no topo de TODA página protegida, antes de qualquer HTML.
 * É o único ponto do sistema que chama session_start().
 */

require_once __DIR__ . '/funcoes.php';

// session_start() centralizado — nunca duplicar em outros arquivos
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retorna os dados do usuário logado, ou null se não houver sessão ativa.
 *
 * @return array|null
 */
function usuario_logado(): ?array
{
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }

    return [
        'usuario_id'     => (int) $_SESSION['usuario_id'],
        'tipo'           => $_SESSION['tipo']  ?? null,
        'nome'           => $_SESSION['nome']  ?? '',
        'login'          => $_SESSION['login'] ?? '',
        'senha_alterada' => (int) ($_SESSION['senha_alterada'] ?? 1),
        'aluno_id'       => isset($_SESSION['aluno_id']) ? (int) $_SESSION['aluno_id'] : null,
    ];
}

/**
 * Protege uma página exigindo sessão ativa do tipo esperado.
 *
 * Regras:
 *  - Sem sessão ou tipo divergente → redireciona para index.php
 *  - Aluno em primeiro acesso (senha_alterada = 0) → redireciona para
 *    aluno/alterar-senha.php (exceto quando já está nessa própria tela,
 *    evitando loop de redirecionamento)
 *
 * @param  string $tipo_esperado 'aluno' ou 'admin'
 * @return void
 */
function verificar_sessao(string $tipo_esperado): void
{
    $usuario = usuario_logado();

    // 1. Sessão inexistente ou perfil incompatível com a área acessada
    if ($usuario === null || $usuario['tipo'] !== $tipo_esperado) {
        redirecionar('../index.php');
    }

    // 2. Primeiro acesso do aluno: a troca de senha é obrigatória
    if ($usuario['tipo'] === 'aluno' && $usuario['senha_alterada'] === 0) {
        $pagina_atual = basename($_SERVER['SCRIPT_NAME'] ?? '');
        if ($pagina_atual !== 'alterar-senha.php') {
            redirecionar('alterar-senha.php');
        }
    }
}
