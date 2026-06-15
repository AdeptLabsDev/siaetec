<?php
/**
 * aluno/enquete.php
 * Enquete do Aluno — registra a intenção alimentar (SIM/NÃO) para a enquete
 * de hoje. O envio é feito via AJAX (fetch) para api/responder.php.
 *
 * O estado de "encerrada" é verificado no servidor (horario_limite da
 * enquete) antes de renderizar.
 */

require_once __DIR__ . '/../includes/auth.php'; // sessão (antes de qualquer HTML)
require_once __DIR__ . '/../includes/db.php';   // $pdo

verificar_sessao('aluno');
$usuario = usuario_logado();

// Enquete ativa de hoje + refeição vinculada
$stmt = $pdo->prepare(
    'SELECT e.id AS enquete_id, e.data_enquete, e.horario_limite, r.titulo
       FROM enquetes e
       JOIN refeicoes r ON r.id = e.refeicao_id
      WHERE e.data_enquete = CURDATE()
      LIMIT 1'
);
$stmt->execute();
$enquete = $stmt->fetch();

$aberta = $enquete ? enquete_aberta($enquete['horario_limite']) : false;

// Resposta já registrada pelo aluno para esta enquete (se houver)
$resposta_atual = null;
if ($enquete && $usuario['aluno_id'] !== null) {
    $st = $pdo->prepare(
        'SELECT resposta FROM intencoes_alimentares
          WHERE aluno_id = :a AND enquete_id = :e LIMIT 1'
    );
    $st->execute([':a' => $usuario['aluno_id'], ':e' => $enquete['enquete_id']]);
    $linha = $st->fetch();
    $resposta_atual = $linha ? $linha['resposta'] : null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Enquete · SIAetec</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* --- Estilos específicos da enquete --- */
        .enquete-wrapper { display: flex; justify-content: center; }
        .enquete-cartao { max-width: 480px; }
        .enquete-cartao h1 { font-size: 1.5rem; margin-bottom: 0.35rem; }
        .enquete-sub { color: var(--cinza-texto); font-size: 0.95rem; margin-bottom: 1.5rem; }

        .opcoes { display: flex; flex-direction: column; gap: 1rem; }
        .opcao {
            min-height: 64px;
            font-size: 1.05rem;
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
        }
        .opcao[aria-pressed="true"] { outline: 3px solid var(--vermelho-institucional); outline-offset: 2px; }

        .confirmacao {
            margin-top: 1.25rem;
            padding: 1rem;
            border-radius: var(--raio-borda-medio);
            background-color: var(--cinza-claro);
            text-align: center;
        }
        .confirmacao[hidden] { display: none; }
        .confirmacao strong { color: var(--vermelho-institucional); }

        .encerrada-aviso {
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: var(--cinza-texto);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar-aluno.php'; ?>

    <main class="conteudo enquete-wrapper">
        <?php if (!$enquete): ?>
            <section class="cartao enquete-cartao">
                <p>Nenhuma refeição disponível para responder hoje.</p>
            </section>
        <?php else: ?>
            <section class="cartao enquete-cartao"
                     data-enquete-id="<?= (int) $enquete['enquete_id'] ?>"
                     data-aberta="<?= $aberta ? '1' : '0' ?>">
                <h1>Você vai almoçar hoje?</h1>
                <p class="enquete-sub">
                    <?= htmlspecialchars($enquete['titulo'], ENT_QUOTES, 'UTF-8') ?> ·
                    <?= htmlspecialchars(formatar_data($enquete['data_enquete'], 'd/m/Y'), ENT_QUOTES, 'UTF-8') ?>
                </p>

                <?php if (!$aberta): ?>
                    <p class="encerrada-aviso">Esta enquete já foi encerrada. Sua resposta não pode mais ser alterada.</p>
                    <div class="confirmacao">
                        <?php if ($resposta_atual !== null): ?>
                            Sua resposta registrada: <strong><?= $resposta_atual === 'sim' ? 'SIM' : 'NÃO' ?></strong>
                        <?php else: ?>
                            Você não respondeu a esta enquete.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="opcoes">
                        <button type="button" class="botao-primario opcao" data-resposta="sim"
                                aria-pressed="<?= $resposta_atual === 'sim' ? 'true' : 'false' ?>">
                            &#10003; SIM, vou almoçar
                        </button>
                        <button type="button" class="botao-secundario opcao" data-resposta="nao"
                                aria-pressed="<?= $resposta_atual === 'nao' ? 'true' : 'false' ?>">
                            &#10007; NÃO vou almoçar
                        </button>
                    </div>

                    <div class="confirmacao" id="confirmacao" <?= $resposta_atual === null ? 'hidden' : '' ?>>
                        <span id="confirmacao-texto">
                            <?php if ($resposta_atual !== null): ?>
                                Sua resposta: <strong><?= $resposta_atual === 'sim' ? 'SIM' : 'NÃO' ?></strong>. Você pode alterá-la enquanto a enquete estiver aberta.
                            <?php endif; ?>
                        </span>
                    </div>

                    <p class="mensagem-erro" id="enquete-erro" hidden></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <?php if ($enquete && $aberta): ?>
    <script>
        (function () {
            const cartao   = document.querySelector('.enquete-cartao');
            const enqueteId = parseInt(cartao.dataset.enqueteId, 10);
            const botoes   = cartao.querySelectorAll('.opcao');
            const confirm  = document.getElementById('confirmacao');
            const confTxt  = document.getElementById('confirmacao-texto');
            const erroEl   = document.getElementById('enquete-erro');
            const endpoint = '../api/responder.php';

            function marcar(resposta) {
                botoes.forEach(b => b.setAttribute('aria-pressed', b.dataset.resposta === resposta ? 'true' : 'false'));
            }

            function mostrarConfirmacao(resposta) {
                const rotulo = resposta === 'sim' ? 'SIM' : 'NÃO';
                confTxt.innerHTML = 'Sua resposta: <strong>' + rotulo + '</strong>. Você pode alterá-la enquanto a enquete estiver aberta.';
                confirm.hidden = false;
                erroEl.hidden = true;
            }

            async function enviar(resposta) {
                erroEl.hidden = true;
                botoes.forEach(b => b.disabled = true);
                try {
                    const resp = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ enquete_id: enqueteId, resposta: resposta })
                    });
                    const dados = await resp.json();
                    if (!resp.ok || dados.erro) {
                        throw new Error(dados.erro || 'Não foi possível registrar sua resposta.');
                    }
                    marcar(resposta);
                    mostrarConfirmacao(resposta);
                } catch (e) {
                    erroEl.textContent = e.message;
                    erroEl.hidden = false;
                } finally {
                    botoes.forEach(b => b.disabled = false);
                }
            }

            botoes.forEach(b => b.addEventListener('click', () => enviar(b.dataset.resposta)));
        })();
    </script>
    <?php endif; ?>
</body>
</html>
