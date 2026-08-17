<?php
/**
 * includes/funcoes.php
 * Funções auxiliares gerais reutilizáveis em todo o sistema.
 * Não produz saída e não depende de sessão nem de banco.
 */

/**
 * Limpa uma entrada de usuário: remove espaços nas pontas e neutraliza HTML.
 *
 * @param  string|null $valor
 * @return string
 */
function sanitizar(?string $valor): string
{
    return htmlspecialchars(trim((string) $valor), ENT_QUOTES, 'UTF-8');
}

/**
 * Redireciona o navegador para um caminho e encerra a execução.
 * Deve ser chamada antes de qualquer saída HTML.
 *
 * @param string $caminho
 * @return never
 */
function redirecionar(string $caminho): void
{
    header('Location: ' . $caminho);
    exit;
}

/**
 * Indica se a enquete ainda está aberta, comparando o horário limite
 * com o momento atual no formato 'Y-m-d H:i:s'.
 *
 * @param  string $horario_limite Ex.: '2026-06-14 11:30:00'
 * @return bool   true se ainda dentro do prazo
 */
function enquete_aberta(string $horario_limite): bool
{
    return date('Y-m-d H:i:s') < $horario_limite;
}

/**
 * Formata uma data/datetime do banco para exibição em português.
 *
 * @param  string $data    Valor vindo do banco (ex.: '2026-06-14 11:30:00')
 * @param  string $formato Formato aceito por date() (padrão: dd/mm/aaaa)
 * @return string
 */
function formatar_data(string $data, string $formato = 'd/m/Y'): string
{
    $timestamp = strtotime($data);
    if ($timestamp === false) {
        return $data;
    }
    return date($formato, $timestamp);
}

/**
 * Gera uma senha provisória sem caracteres visualmente ambíguos.
 *
 * @param  int $tamanho
 * @return string
 */
function gerar_senha_aleatoria($tamanho = 8)
{
    $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $senha = '';
    $tamanho = (int) $tamanho;
    $ultimo_indice = strlen($alfabeto) - 1;

    for ($i = 0; $i < $tamanho; $i++) {
        $senha .= $alfabeto[random_int(0, $ultimo_indice)];
    }

    return $senha;
}

/**
 * Escapa um campo para CSV compatível com Excel PT-BR (separador ';').
 * Neutraliza injeção de fórmula (=, +, -, @) prefixando aspa simples e
 * envolve em aspas (dobrando as internas) quando há aspas, ';' ou quebra
 * de linha. Espelha a mesma regra usada no gerador client-side.
 *
 * @param  string $valor
 * @return string
 */
function csv_campo_credencial(string $valor): string
{
    if (preg_match('/^[=+\-@\t\r]/', $valor) === 1) {
        $valor = "'" . $valor;
    }
    if (preg_match('/[";\r\n]/', $valor) === 1) {
        $valor = '"' . str_replace('"', '""', $valor) . '"';
    }
    return $valor;
}

/**
 * Monta o conteúdo completo de um CSV de credenciais: BOM UTF-8 + cabeçalho +
 * linhas, separador ';' e quebras CRLF. Colunas fixas na ordem:
 * nome, rm, email, senha, email_enviado. Não persiste nada — apenas devolve
 * a string, que o chamador transmite como download.
 *
 * @param  array<int,array<string,scalar|null>> $linhas
 * @return string
 */
function montar_csv_credenciais(array $linhas): string
{
    $colunas = ['nome', 'rm', 'email', 'senha', 'email_enviado'];
    $saida = "\xEF\xBB\xBF" . implode(';', $colunas) . "\r\n";

    foreach ($linhas as $linha) {
        $campos = [];
        foreach ($colunas as $coluna) {
            $campos[] = csv_campo_credencial((string) ($linha[$coluna] ?? ''));
        }
        $saida .= implode(';', $campos) . "\r\n";
    }

    return $saida;
}
