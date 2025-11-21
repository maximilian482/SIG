<?php
session_start();
require_once '../dados/conexao.php';
date_default_timezone_set('America/Sao_Paulo');

$conn = conectar();

// Captura dados do formulário
$loja_id       = intval($_POST['loja_id'] ?? 0);
$titulo        = trim($_POST['titulo'] ?? '');
$descricao     = trim($_POST['descricao'] ?? '');
$solicitante_id = $_SESSION['id_funcionario'] ?? 0; // vem da sessão

// Validação
if (!$loja_id) {
  echo "❌ Loja não informada.";
  exit;
}
if (!$titulo) {
  echo "❌ Informe o título da inconformidade.";
  exit;
}
if (!$descricao) {
  echo "❌ Informe a descrição da inconformidade.";
  exit;
}
if (!$solicitante_id) {
  echo "❌ Usuário não identificado na sessão.";
  exit;
}

// Código compacto: INC-YYMMDDHHMMSS-XXXX (4 chars aleatórios base36)
$rand = strtoupper(substr(base_convert(random_int(0, 36**6 - 1), 10, 36), 0, 6));
$codigo = 'INC-' . date('ymdHis') . '-' . $rand;


// Prepara INSERT
$stmt = $conn->prepare("
  INSERT INTO inconformidades (
    codigo_inconformidade, loja_id, titulo, descricao, abertura, status, solicitante_id
  ) VALUES (?, ?, ?, ?, NOW(), 'Aberto', ?)
");
$stmt->bind_param("sissi", $codigo, $loja_id, $titulo, $descricao, $solicitante_id);


// Executa
if ($stmt->execute()) {
  echo "✅ Inconformidade registrada com sucesso! Código: {$codigo}";
} else {
  echo "❌ Erro ao registrar: " . $conn->error;
}
