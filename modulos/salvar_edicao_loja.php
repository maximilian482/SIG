<?php
$id = $_POST['id'] ?? '';
$nome = $_POST['nome'] ?? '';
$cnpj = $_POST['cnpj'] ?? '';
$responsavel = $_POST['responsavel'] ?? '';
$validade = $_POST['validade'] ?? '';

if (!$id || !$nome || !$cnpj || !$responsavel) {
  header('Location: editar_loja.php?nome=' . urlencode($id));
  exit;
}

$arquivo = '../dados/gerencial.json';
$dados = file_exists($arquivo) ? json_decode(file_get_contents($arquivo), true) : [];

$dados[$id] = [
  'nome' => $nome,
  'cnpj' => $cnpj,
  'responsavel' => $responsavel,
  'certificado_digital' => [
    'validade' => $validade
  ]
];

file_put_contents($arquivo, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Location: loja.php?nome=' . urlencode($id));
exit;
