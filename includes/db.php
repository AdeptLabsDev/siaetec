<?php
/**
 * includes/db.php
 * Conexão com o banco de dados MySQL via PDO.
 *
 * Carrega as credenciais definidas em config.php e disponibiliza a
 * instância $pdo para os demais arquivos que incluírem este script.
 * Uso: require_once __DIR__ . '/db.php';  // $pdo fica disponível em escopo
 */

require_once __DIR__ . '/config.php';

// Opções da conexão PDO
$opcoes_pdo = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Erros viram exceções (PDOException)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Retorna arrays associativos por padrão
    PDO::ATTR_EMULATE_PREPARES   => false,                   // Usa prepared statements nativos do MySQL
];

// Data Source Name — define driver, host, banco e codificação
$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opcoes_pdo);
} catch (PDOException $e) {
    // Falha de conexão: não expõe credenciais; relança como exceção tratável
    http_response_code(500);
    throw new RuntimeException('Falha ao conectar ao banco de dados.', (int) $e->getCode(), $e);
}
