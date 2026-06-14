<?php
/**
 * api/resultados.php
 * Endpoint JSON (admin) — dados consolidados de uma refeição.
 * Sem parâmetro, usa a refeição da data de hoje.
 * Aceita ?refeicao_id=N para consultar uma refeição específica.
 */

require_once __DIR__ . '/../includes/auth.php'; // inicia sessão (não redireciona)
require_once __DIR__ . '/../includes/db.php';   // $pdo

header('Content-Type: application/json; charset=utf-8');

$u = usuario_logado();
if ($u === null || $u['tipo'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $refeicao_id = filter_input(INPUT_GET, 'refeicao_id', FILTER_VALIDATE_INT);

    if ($refeicao_id) {
        $stmt = $pdo->prepare('SELECT id, titulo, data_refeicao, horario_limite FROM refeicoes WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $refeicao_id]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, titulo, data_refeicao, horario_limite
               FROM refeicoes WHERE data_refeicao = :hoje
              ORDER BY horario_limite DESC LIMIT 1'
        );
        $stmt->execute([':hoje' => date('Y-m-d')]);
    }
    $refeicao = $stmt->fetch();

    if (!$refeicao) {
        echo json_encode(['refeicao' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $total_alunos = (int) $pdo->query('SELECT COUNT(*) FROM alunos WHERE ativo = 1')->fetchColumn();

    $st = $pdo->prepare(
        "SELECT COALESCE(SUM(resposta = 'sim'), 0) AS sim,
                COALESCE(SUM(resposta = 'nao'), 0) AS nao
           FROM intencoes_alimentares WHERE refeicao_id = :r"
    );
    $st->execute([':r' => $refeicao['id']]);
    $linha = $st->fetch();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao consultar os resultados.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sim = (int) $linha['sim'];
$nao = (int) $linha['nao'];
$responderam  = $sim + $nao;
$sem_resposta = max(0, $total_alunos - $responderam);

echo json_encode([
    'refeicao_id'  => (int) $refeicao['id'],
    'titulo'       => $refeicao['titulo'],
    'data'         => $refeicao['data_refeicao'],
    'aberta'       => enquete_aberta($refeicao['horario_limite']),
    'total_alunos' => $total_alunos,
    'responderam'  => $responderam,
    'sim'          => $sim,
    'nao'          => $nao,
    'sem_resposta' => $sem_resposta,
], JSON_UNESCAPED_UNICODE);
