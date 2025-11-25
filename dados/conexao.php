<?php
// conexao.php dentro de public_html/dados
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Carrega o .env da raiz (public_html)
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

function conectar() {
    $host    = $_ENV['DB_HOST'] ?? '';
    $usuario = $_ENV['DB_USER'] ?? '';
    $senha   = $_ENV['DB_PASS'] ?? '';
    $banco   = $_ENV['DB_NAME'] ?? '';

    $conn = new mysqli($host, $usuario, $senha, $banco, 3306);

    if ($conn->connect_error) {
        die("❌ Falha na conexão: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}
?>
