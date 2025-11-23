<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Carrega variáveis do .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

function conectar() {
    $host    = $_ENV['DB_HOST'] ?? '';
    $usuario = $_ENV['DB_USER'] ?? '';
    $senha   = $_ENV['DB_PASS'] ?? '';
    $banco   = $_ENV['DB_NAME'] ?? '';

    $conn = new mysqli($host, $usuario, $senha, $banco);

    if ($conn->connect_error) {
        die("❌ Falha na conexão com o banco: " . $conn->connect_error);
    }

    $conn->set_charset("utf8");
    return $conn;
}
