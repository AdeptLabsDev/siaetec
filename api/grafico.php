<?php
/**
 * api/grafico.php
 * Endpoint JSON (admin) — histórico de votos agrupado por data da refeição.
 * Saída: [ { "data": "2026-06-14", "sim": 87, "nao": 12, "sem_resposta": 15 }, ... ]
 * sem_resposta = total de alunos ativos − (sim + nao) por data.
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
    $total_ativos = (int) $pdo->query('SELECT COUNT(*) FROM alunos WHERE ativo = 1')->fetchColumn();

    $linhas = $pdo->query(
        "SELECT r.data_refeicao AS data,
                COALESCE(SUM(i.resposta = 'sim'), 0) AS sim,
                COALESCE(SUM(i.resposta = 'nao'), 0) AS nao
           FROM refeicoes r
           LEFT JOIN intencoes_alimentares i ON i.refeicao_id = r.id
          GROUP BY r.data_refeicao
          ORDER BY r.data_refeicao ASC"
    )->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao consultar o histórico.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$resultado = [];
foreach ($linhas as $linha) {
    $sim = (int) $linha['sim'];
    $nao = (int) $linha['nao'];
    $resultado[] = [
        'data'         => $linha['data'],
        'sim'          => $sim,
        'nao'          => $nao,
        'sem_resposta' => max(0, $total_ativos - ($sim + $nao)),
    ];
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
