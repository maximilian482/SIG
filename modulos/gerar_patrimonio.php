<?php
require_once '../dados/conexao.php';

$loja_id = intval($_GET['loja_id'] ?? 0);

if ($loja_id <= 0) {
  echo '';
  exit;
}

// Buscar o código da loja
$stmt = $conn->prepare("SELECT codigo_loja FROM lojas WHERE id = ?");
$stmt->bind_param("i", $loja_id);
$stmt->execute();
$stmt->bind_result($codigoLoja);
if (!$stmt->fetch()) {
  echo '';
  exit;
}
$stmt->close();

// Iniciar transação segura
$conn->begin_transaction();

$stmt = $conn->prepare("SELECT ultimo_numero FROM controle_sequencial WHERE loja_id = ? FOR UPDATE");
$stmt->bind_param("i", $loja_id);
$stmt->execute();
$stmt->bind_result($ultimo);
if ($stmt->fetch()) {
  $novo = $ultimo + 1;
  $stmt->close();

  $stmt = $conn->prepare("UPDATE controle_sequencial SET ultimo_numero = ? WHERE loja_id = ?");
  $stmt->bind_param("ii", $novo, $loja_id);
  $stmt->execute();
} else {
  $stmt->close();
  $novo = 1;
  $stmt = $conn->prepare("INSERT INTO controle_sequencial (loja_id, ultimo_numero) VALUES (?, ?)");
  $stmt->bind_param("ii", $loja_id, $novo);
  $stmt->execute();
}

$conn->commit();

// Gerar código final
$codigoPatrimonio = $codigoLoja . str_pad($novo, 3, '0', STR_PAD_LEFT);
echo $codigoPatrimonio;
