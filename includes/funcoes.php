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
