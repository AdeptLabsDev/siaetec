<?php
/**
 * includes/email.php
 * Funções de envio de e-mail transacional pela API da Brevo.
 */

/**
 * Envia ao aluno as credenciais provisórias de acesso ao SIAETEC.
 *
 * @param  string $email
 * @param  string $nome
 * @param  string $rm
 * @param  string $senha
 * @return bool
 */
function enviar_credenciais($email, $nome, $rm, $senha)
{
    if (!defined('BREVO_API_KEY') || BREVO_API_KEY === '') {
        return false;
    }

    if (!defined('BREVO_REMETENTE_EMAIL') || BREVO_REMETENTE_EMAIL === '') {
        return false;
    }

    if (!defined('BREVO_REMETENTE_NOME') || BREVO_REMETENTE_NOME === '') {
        return false;
    }

    $api_key = trim((string) BREVO_API_KEY);
    $remetente_email = trim((string) BREVO_REMETENTE_EMAIL);
    $remetente_nome = trim((string) BREVO_REMETENTE_NOME);
    $email = trim((string) $email);

    if ($api_key === '') {
        return false;
    }

    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }

    if (!function_exists('curl_init')) {
        return false;
    }

    if ($remetente_email === '' || filter_var($remetente_email, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }

    if ($remetente_nome === '') {
        return false;
    }

    $nome_html = htmlspecialchars((string) $nome, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $rm_html = htmlspecialchars((string) $rm, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $senha_html = htmlspecialchars((string) $senha, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $html = '<p>Olá, ' . $nome_html . '!</p>'
        . '<p>Suas credenciais de acesso ao SIAETEC são:</p>'
        . '<p><strong>RM:</strong> ' . $rm_html . '<br>'
        . '<strong>Senha provisória:</strong> ' . $senha_html . '</p>'
        . '<p>No primeiro acesso, o sistema solicitará a troca desta senha.</p>';

    $corpo = json_encode([
        'sender' => [
            'name' => $remetente_nome,
            'email' => $remetente_email,
        ],
        'to' => [[
            'email' => $email,
            'name' => (string) $nome,
        ]],
        'subject' => 'Credenciais de acesso ao SIAETEC',
        'htmlContent' => $html,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($corpo === false) {
        return false;
    }

    $curl = null;

    try {
        $curl = curl_init('https://api.brevo.com/v3/smtp/email');

        if ($curl === false) {
            return false;
        }

        $configurado = curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $corpo,
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $api_key,
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        if ($configurado === false) {
            return false;
        }

        $resposta = curl_exec($curl);
        $codigo_http = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $erro_curl = curl_error($curl);
        $mensagem_api = '';

        if (is_string($resposta) && $resposta !== '') {
            $dados_resposta = json_decode($resposta, true);

            if (
                is_array($dados_resposta)
                && isset($dados_resposta['message'])
                && is_scalar($dados_resposta['message'])
            ) {
                $mensagem_api = (string) $dados_resposta['message'];
            }
        }

        if ($resposta === false || $codigo_http !== 201) {
            $erro_curl = str_replace(["\r", "\n"], ' ', $erro_curl);
            $mensagem_api = str_replace(["\r", "\n"], ' ', $mensagem_api);

            error_log(
                '[SIAETEC][Brevo] HTTP ' . $codigo_http
                . ' | cURL: ' . ($erro_curl !== '' ? $erro_curl : '-')
                . ' | API: ' . ($mensagem_api !== '' ? $mensagem_api : '-')
            );
            return false;
        }

        return true;
    } catch (Throwable $erro) {
        return false;
    } finally {
        if ($curl !== null && $curl !== false) {
            curl_close($curl);
        }
    }
}
