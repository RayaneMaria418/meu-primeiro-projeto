<?php
// Conexão com o banco (os valores podem ser trocados por variáveis de ambiente)
define('HOST', getenv('DB_HOST') ?: 'localhost');
define('USER', getenv('DB_USER') ?: 'root');
define('PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('BASE', getenv('DB_NAME') ?: 'postodesaude');

define('NOME_SISTEMA', 'Sistema de Gestão para Posto de Saúde');
define('NOME_UNIDADE', 'Unidade Básica de Saúde');

// Chave usada na criptografia AES-256 dos dados sensíveis e na assinatura das movimentações.
// TROQUE este valor ao publicar o sistema.
define('CHAVE_SEGURANCA', getenv('CHAVE_SEGURANCA') ?: 'aps-posto-de-saude-troque-esta-chave');

// RN-RNF03: em produção, ative para recusar acesso sem HTTPS
define('FORCAR_HTTPS', false);
// RN-RNF05: ative durante paradas programadas (somente o administrador continua acessando)
define('MODO_MANUTENCAO', false);

date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new MySQLi(HOST, USER, PASS, BASE);
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '" . date('P') . "'");
?>
