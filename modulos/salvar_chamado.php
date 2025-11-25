<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once '../dados/conexao.php'; // sua função conectar()

$conn = conectar();

// --- Geração de código sequencial CHM + 3 dígitos ---
$result = $conn->query("SELECT MAX(CAST(SUBSTRING(codigo_chamado, 4) AS SIGNED)) AS ultimo FROM chamados");
$row = $result->fetch_assoc();
$ultimoCodigo = isset($row['ultimo']) ? intval($row['ultimo']) : 0;

$novoCodigo   = str_pad($ultimoCodigo + 1, 3, '0', STR_PAD_LEFT);
$codigoChamado = 'CHM' . $novoCodigo;


// --- Preparar dados ---
$titulo        = $_POST['titulo'] ?? '';
$descricao     = $_POST['descricao'] ?? '';
$setorDestino  = $_POST['setor_destino'] ?? '';
$lojaOrigem    = $_SESSION['loja'] ?? '';
$solicitanteId = $_SESSION['funcionario_id'] ?? 0;
$dataAbertura  = date('Y-m-d H:i:s');
$status        = 'Aberto';

// --- Inserir no banco ---
$stmt = $conn->prepare("INSERT INTO chamados 
  (codigo_chamado, titulo, descricao, setor_destino, loja_origem, solicitante_id, data_abertura, status) 
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param(
  "ssssisss",
  $codigoChamado,
  $titulo,
  $descricao,
  $setorDestino,
  $lojaOrigem,
  $solicitanteId,
  $dataAbertura,
  $status
);

$ok = $stmt->execute();

// --- Resposta em JSON ---
header('Content-Type: application/json; charset=utf-8');

if ($ok) {
  echo json_encode([
    'ok'       => true,
    'mensagem' => "✅ Chamado {$codigoChamado} registrado com sucesso!",
    'codigo'   => $codigoChamado
  ]);
} else {
  echo json_encode([
    'ok'       => false,
    'mensagem' => "❌ Erro ao salvar chamado: " . $stmt->error
  ]);
}

$stmt->close();
$conn->close();
exit;
?>
