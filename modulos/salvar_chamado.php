<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

$arquivo = '../dados/chamados.json';
$chamados = json_decode(@file_get_contents($arquivo), true);
$chamados = is_array($chamados) ? $chamados : [];

$titulo    = $_POST['titulo'] ?? '';
$descricao = $_POST['descricao'] ?? '';
$setor     = $_POST['cargo'] ?? '';

$usuario     = $_SESSION['usuario'] ?? '';
$nomeUsuario = $_SESSION['nome'] ?? $usuario;
$lojaUsuario = $_SESSION['loja'] ?? '';

if (!$titulo || !$descricao || !$setor || !$lojaUsuario) {
  header('Location: chamados_publico.php?erro=' . urlencode('Preencha todos os campos.'));
  exit;
}

$id = 'CHM' . str_pad(strval(rand(10000, 99999)), 5, '0', STR_PAD_LEFT);

$chamados[] = [
  'id' => $id,
  'titulo' => $titulo,
  'descricao' => $descricao,
  'setor_destino' => $setor,
  'loja_origem' => $lojaUsuario,
  'usuario_solicitante' => $nomeUsuario,
  'data_abertura' => date('Y-m-d H:i:s'),
  'status' => 'aberto',
  'usuario_responsavel' => '',
  'data_assumido' => '',
  'solucao' => '',
  'data_solucao' => '',
  'avaliacao' => '',
  'data_avaliacao' => ''
];

file_put_contents($arquivo, json_encode($chamados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Location: acompanhar_chamados_publico.php?sucesso=' . urlencode('Chamado registrado com sucesso.'));
exit;
?>
