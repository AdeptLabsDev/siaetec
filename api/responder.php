<?php
/**
 * api/responder.php
 * Endpoint JSON — registra ou atualiza a intenção alimentar do aluno.
 * Consumido via fetch (AJAX) por aluno/enquete.php.
 *
 * Entrada (JSON ou form): { enquete_id: int, resposta: 'sim'|'nao' }
 * Códigos: 200 sucesso · 400 inválido · 401 sem sessão · 403 encerrada
 *          · 404 enquete inexistente · 405 método · 500 erro interno
 */

require_once __DIR__ . '/../includes/auth.php'; // inicia a sessão (não redireciona)
require_once __DIR__ . '/../includes/db.php';   // $pdo

header('Content-Type: application/json; charset=utf-8');

/** Encerra a requisição devolvendo um JSON e o código HTTP correspondente. */
function responder_json(int $codigo, array $dados): void
{
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

// Apenas POST é aceito
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_json(405, ['erro' => 'Método não permitido.']);
}

// 1. Exige sessão de aluno (nunca confia no aluno_id vindo do cliente)
$usuario = usuario_logado();
if ($usuario === null || $usuario['tipo'] !== 'aluno' || $usuario['aluno_id'] === null) {
    responder_json(401, ['erro' => 'Não autorizado.']);
}

// 2. Lê o corpo da requisição (aceita JSON ou form-urlencoded)
$bruto = file_get_contents('php://input');
$dados = json_decode($bruto, true);
if (!is_array($dados)) {
    $dados = $_POST;
}

$enquete_id = filter_var($dados['enquete_id'] ?? null, FILTER_VALIDATE_INT);
$resposta   = $dados['resposta'] ?? '';

// 3. Validação de entrada
if ($enquete_id === false || $enquete_id === null || !in_array($resposta, ['sim', 'nao'], true)) {
    responder_json(400, ['erro' => 'Dados inválidos.']);
}

try {
    // 4. A enquete precisa existir
    $stmt = $pdo->prepare('SELECT id, horario_limite FROM enquetes WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $enquete_id]);
    $enquete = $stmt->fetch();
    if (!$enquete) {
        responder_json(404, ['erro' => 'Enquete não encontrada.']);
    }

    // 5. A enquete precisa estar aberta (verificação reativa pelo horário limite)
    if (!enquete_aberta($enquete['horario_limite'])) {
        responder_json(403, ['erro' => 'Enquete encerrada.']);
    }

    // 6. UPSERT respeitando o UNIQUE (aluno_id, enquete_id):
    //    insere a resposta ou atualiza a existente, registrando alterado_em.
    $sql = 'INSERT INTO intencoes_alimentares (aluno_id, enquete_id, resposta)
            VALUES (:aluno, :enquete, :resposta)
            ON DUPLICATE KEY UPDATE resposta = :resposta_upd, alterado_em = CURRENT_TIMESTAMP';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':aluno'        => $usuario['aluno_id'],
        ':enquete'      => $enquete_id,
        ':resposta'     => $resposta,
        ':resposta_upd' => $resposta,
    ]);
} catch (PDOException $e) {
    responder_json(500, ['erro' => 'Erro ao registrar a resposta.']);
}

responder_json(200, ['sucesso' => true, 'resposta' => $resposta]);
