<?php
$nome = $_POST['nome'] ?? '';
$cnpj = $_POST['cnpj'] ?? '';
$responsavel = $_POST['responsavel'] ?? '';

if (!$nome || !$cnpj || !$responsavel) {
  header('Location: nova_loja.php');
  exit;
}

$arquivo = '../dados/gerencial.json';
$dados = file_exists($arquivo) ? json_decode(file_get_contents($arquivo), true) : [];

$dados[$nome] = [
  'nome' => $nome,
  'cnpj' => $cnpj,
  'responsavel' => $responsavel,
  'certificado_digital' => [] // será preenchido depois
];

file_put_contents($arquivo, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Location: lojas.php');
exit;
