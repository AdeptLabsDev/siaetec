<?php
/**
 * aluno/sugestoes.php
 * Envio de sugestões do aluno (ui-design §7).
 *
 * Renderiza o formulário (server-side) e processa o envio via AJAX:
 * um POST (fetch) para a própria página grava em `sugestoes` e devolve JSON,
 * sem recarregar a tela.
 */

require_once __DIR__ . '/../includes/auth.php'; // sessão (antes de qualquer HTML)
require_once __DIR__ . '/../includes/db.php';   // $pdo

verificar_sessao('aluno');
$usuario = usuario_logado();

$ASSUNTOS = ['cardapio', 'atendimento', 'sistema', 'outro'];

// --- Processamento AJAX (POST → JSON) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    if ($usuario['aluno_id'] === null) {
        http_response_code(401);
        echo json_encode(['erro' => 'Não autorizado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $assunto  = $_POST['assunto'] ?? '';
    $mensagem = trim($_POST['mensagem'] ?? '');

    if (!in_array($assunto, $ASSUNTOS, true) || $mensagem === '' || mb_strlen($mensagem) > 500) {
        http_response_code(400);
        echo json_encode(['erro' => 'Preencha um assunto válido e uma mensagem de até 500 caracteres.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO sugestoes (aluno_id, assunto, mensagem) VALUES (:a, :assunto, :mensagem)'
        );
        $stmt->execute([
            ':a'        => $usuario['aluno_id'],
            ':assunto'  => $assunto,
            ':mensagem' => $mensagem,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao enviar a sugestão.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['sucesso' => true], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Sugestões · SIAetec</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* --- Estilos específicos da página de sugestões --- */
        .sugestao-cartao { max-width: 560px; margin: 0 auto; }
        .sugestao-cartao h1 { font-size: 1.5rem; margin-bottom: 0.35rem; }
        .sugestao-sub { color: var(--cinza-texto); font-size: 0.95rem; margin-bottom: 1.5rem; }
        .grupo-campo { margin-bottom: 1rem; }
        .area-texto {
            width: 100%;
            min-height: 120px;
            resize: vertical;
            font-family: var(--fonte-principal);
            font-size: 16px;
            color: var(--preto);
            background-color: var(--branco);
            border: 1px solid var(--cinza-borda);
            border-radius: var(--raio-borda-pequeno);
            padding: 0.7rem 0.85rem;
        }
        .area-texto:focus { outline: none; border-color: var(--vermelho-atrativo); }
        .contador-caracteres {
            display: block;
            text-align: right;
            font-size: 0.75rem;
            color: var(--cinza-texto);
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar-aluno.php'; ?>

    <main class="conteudo">
        <section class="cartao sugestao-cartao">
            <h1>Envie sua sugestão</h1>
            <p class="sugestao-sub">Sua opinião ajuda a melhorar a merenda e o sistema. Conte para a gente.</p>

            <form id="form-sugestao" method="post" action="sugestoes.php">
                <div class="grupo-campo">
                    <label class="rotulo" for="assunto">Assunto</label>
                    <select class="campo" id="assunto" name="assunto" required>
                        <option value="cardapio">Cardápio</option>
                        <option value="atendimento">Atendimento</option>
                        <option value="sistema">Sistema</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>

                <div class="grupo-campo">
                    <label class="rotulo" for="mensagem">Mensagem</label>
                    <textarea class="area-texto" id="mensagem" name="mensagem" rows="4" maxlength="500" required></textarea>
                    <span class="contador-caracteres"><span id="contador-num">0</span>/500</span>
                </div>

                <button type="submit" class="botao-primario">Enviar Sugestão</button>
                <p class="mensagem-sucesso" id="sugestao-sucesso" hidden>Sugestão enviada com sucesso!</p>
                <p class="mensagem-erro" id="sugestao-erro" hidden></p>
            </form>
        </section>
    </main>

    <script>
        (function () {
            const form     = document.getElementById('form-sugestao');
            const mensagem = document.getElementById('mensagem');
            const contador = document.getElementById('contador-num');
            const sucesso  = document.getElementById('sugestao-sucesso');
            const erro     = document.getElementById('sugestao-erro');

            mensagem.addEventListener('input', function () {
                contador.textContent = mensagem.value.length;
            });

            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                sucesso.hidden = true;
                erro.hidden = true;

                try {
                    const resp = await fetch('sugestoes.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams(new FormData(form))
                    });
                    const dados = await resp.json();
                    if (!resp.ok || dados.erro) {
                        throw new Error(dados.erro || 'Não foi possível enviar a sugestão.');
                    }
                    form.reset();
                    contador.textContent = '0';
                    sucesso.hidden = false;
                } catch (err) {
                    erro.textContent = err.message;
                    erro.hidden = false;
                }
            });
        })();
    </script>
</body>
</html>
