<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

$id = $_POST['id'] ?? '';
$avaliacao = $_POST['avaliacao'] ?? '';
$justificativa = $_POST['justificativa'] ?? '';

if (!$id || !$avaliacao) {
  echo "Dados incompletos.";
  exit;
}

$arquivo = '../dados/inconformidades.json';
$inconformidades = json_decode(@file_get_contents($arquivo), true) ?: [];

foreach ($inconformidades as &$i) {
  if ($i['id'] === $id) {
    $i['avaliacao'] = $avaliacao;
    $i['avaliacao_justificativa'] = $justificativa;
    $i['avaliacao_data'] = date('Y-m-d H:i:s');
    $i['status'] = 'Encerrado';
    break;
  }
}

file_put_contents($arquivo, json_encode($inconformidades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Avaliação registrada com sucesso.";
