<?php
/**
 * api/enviar-resumo.php
 * Endpoint JSON (admin) — monta o resumo da refeição, envia ao WhatsApp da
 * cozinha via Evolution API (cURL) e registra o envio em `resumos_envio`.
 *
 * Entrada: { refeicao_id: int }
 */

require_once __DIR__ . '/../includes/auth.php'; // inicia sessão (não redireciona)
require_once __DIR__ . '/../includes/db.php';   // $pdo

header('Content-Type: application/json; charset=utf-8');

function responder_json(int $codigo, array $dados): void
{
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

$u = usuario_logado();
if ($u === null || $u['tipo'] !== 'admin') {
    responder_json(401, ['erro' => 'Não autorizado.']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_json(405, ['erro' => 'Método não permitido.']);
}

// Lê o corpo (JSON ou form)
$dados = json_decode(file_get_contents('php://input'), true);
if (!is_array($dados)) {
    $dados = $_POST;
}
$refeicao_id = filter_var($dados['refeicao_id'] ?? null, FILTER_VALIDATE_INT);
if (!$refeicao_id) {
    responder_json(400, ['erro' => 'Refeição inválida.']);
}

try {
    $stmt = $pdo->prepare('SELECT id, titulo, descricao, data_refeicao FROM refeicoes WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $refeicao_id]);
    $refeicao = $stmt->fetch();
    if (!$refeicao) {
        responder_json(404, ['erro' => 'Refeição não encontrada.']);
    }

    // Totais consolidados
    $total_alunos = (int) $pdo->query('SELECT COUNT(*) FROM alunos WHERE ativo = 1')->fetchColumn();
    $st = $pdo->prepare(
        "SELECT COALESCE(SUM(resposta = 'sim'), 0) AS sim,
                COALESCE(SUM(resposta = 'nao'), 0) AS nao
           FROM intencoes_alimentares WHERE refeicao_id = :r"
    );
    $st->execute([':r' => $refeicao_id]);
    $tot = $st->fetch();
} catch (PDOException $e) {
    responder_json(500, ['erro' => 'Erro ao consultar os dados da refeição.']);
}

$sim = (int) $tot['sim'];
$nao = (int) $tot['nao'];
$responderam  = $sim + $nao;
$sem_resposta = max(0, $total_alunos - $responderam);

// Monta a mensagem do WhatsApp (context.md §7.6)
$mensagem  = "*SIAetec — Resumo da merenda*\n";
$mensagem .= 'Data: ' . formatar_data($refeicao['data_refeicao'], 'd/m/Y') . "\n";
$mensagem .= 'Cardápio: ' . ($refeicao['titulo']) . "\n";
if (!empty($refeicao['descricao'])) {
    $mensagem .= $refeicao['descricao'] . "\n";
}
$mensagem .= "\n";
$mensagem .= 'Alunos ativos: ' . $total_alunos . "\n";
$mensagem .= 'Responderam: ' . $responderam . "\n";
$mensagem .= 'SIM: ' . $sim . "\n";
$mensagem .= 'NÃO: ' . $nao . "\n";
$mensagem .= 'Sem resposta: ' . $sem_resposta . "\n";

$destino = defined('WHATSAPP_DESTINO') ? WHATSAPP_DESTINO : '';

// Sem configuração da Evolution API não há o que enviar (ambiente de dev)
if (!defined('EVOLUTION_API_URL') || EVOLUTION_API_URL === '' || $destino === '') {
    responder_json(503, [
        'erro'     => 'Evolution API não configurada. Preencha as constantes em includes/config.php.',
        'mensagem' => $mensagem,
    ]);
}

// Requisição cURL para a Evolution API (endpoint padrão de envio de texto)
// OBS: confirmar o caminho/payload conforme a versão da Evolution API em uso.
$url = rtrim(EVOLUTION_API_URL, '/') . '/message/sendText/' . EVOLUTION_INSTANCIA;
$payload = json_encode(['number' => $destino, 'text' => $mensagem], JSON_UNESCAPED_UNICODE);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'apikey: ' . EVOLUTION_API_TOKEN,
    ],
    CURLOPT_TIMEOUT        => 15,
]);
$resposta_api = curl_exec($ch);
$http_codigo  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$erro_curl    = curl_error($ch);
curl_close($ch);

$sucesso = ($erro_curl === '' && $http_codigo >= 200 && $http_codigo < 300);
$status  = $sucesso ? 'enviado' : 'erro';

// Registra o envio (histórico/auditoria)
try {
    $stmt = $pdo->prepare(
        'INSERT INTO resumos_envio (refeicao_id, admin_id, mensagem, destinatario, status)
         VALUES (:refeicao, :admin, :mensagem, :destino, :status)'
    );
    $stmt->execute([
        ':refeicao' => $refeicao_id,
        ':admin'    => $u['usuario_id'],
        ':mensagem' => $mensagem,
        ':destino'  => $destino,
        ':status'   => $status,
    ]);
} catch (PDOException $e) {
    // Falha ao registrar não deve mascarar o resultado do envio
}

if (!$sucesso) {
    responder_json(502, ['erro' => 'Falha ao enviar para a Evolution API.', 'detalhe' => $erro_curl ?: ('HTTP ' . $http_codigo)]);
}

responder_json(200, ['sucesso' => true, 'mensagem' => $mensagem]);
