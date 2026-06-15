<?php
/**
 * api/resultados.php
 * Endpoint JSON (admin) — dados consolidados de uma enquete.
 * Sem parâmetro, usa a enquete da data de hoje.
 * Aceita ?enquete_id=N para consultar uma enquete específica.
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
    $enquete_id = filter_input(INPUT_GET, 'enquete_id', FILTER_VALIDATE_INT);

    if ($enquete_id) {
        $stmt = $pdo->prepare(
            'SELECT e.id, e.data_enquete, e.horario_limite, r.titulo
               FROM enquetes e
               JOIN refeicoes r ON r.id = e.refeicao_id
              WHERE e.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $enquete_id]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT e.id, e.data_enquete, e.horario_limite, r.titulo
               FROM enquetes e
               JOIN refeicoes r ON r.id = e.refeicao_id
              WHERE e.data_enquete = :hoje LIMIT 1'
        );
        $stmt->execute([':hoje' => date('Y-m-d')]);
    }
    $enquete = $stmt->fetch();

    if (!$enquete) {
        echo json_encode(['enquete' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $total_alunos = (int) $pdo->query('SELECT COUNT(*) FROM alunos WHERE ativo = 1')->fetchColumn();

    $st = $pdo->prepare(
        "SELECT COALESCE(SUM(resposta = 'sim'), 0) AS sim,
                COALESCE(SUM(resposta = 'nao'), 0) AS nao
           FROM intencoes_alimentares WHERE enquete_id = :e"
    );
    $st->execute([':e' => $enquete['id']]);
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
    'enquete_id'   => (int) $enquete['id'],
    'titulo'       => $enquete['titulo'],
    'data'         => $enquete['data_enquete'],
    'aberta'       => enquete_aberta($enquete['horario_limite']),
    'total_alunos' => $total_alunos,
    'responderam'  => $responderam,
    'sim'          => $sim,
    'nao'          => $nao,
    'sem_resposta' => $sem_resposta,
], JSON_UNESCAPED_UNICODE);
