<?php
session_start();

// Protege contra acesso direto
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo 'Este endpoint aceita apenas requisições POST.';
  exit;
}

if (!isset($_SESSION['usuario'])) {
  http_response_code(403);
  echo 'Acesso negado.';
  exit;
}

$usuario   = $_SESSION['usuario'];
$id        = trim($_POST['id'] ?? '');
$avaliacao = strtolower(trim($_POST['avaliacao'] ?? ''));

if (!$id || !in_array($avaliacao, ['sim', 'nao'])) {
  http_response_code(400);
  echo 'Dados inválidos.';
  exit;
}

$caminho = '../dados/inconformidades.json';
$inconformidades = json_decode(@file_get_contents($caminho), true);
$inconformidades = is_array($inconformidades) ? $inconformidades : [];

$encontrado = false;

foreach ($inconformidades as &$i) {
  if (($i['id'] ?? '') === $id) {
    // Verifica se o usuário é o solicitante
    if (($i['usuario_solicitante'] ?? '') !== $usuario) {
      http_response_code(403);
      echo 'Você não tem permissão para avaliar esta inconformidade.';
      exit;
    }

    // Avaliação
    $i['avaliacao'] = $avaliacao;
    $i['avaliacao_data'] = date('Y-m-d H:i:s');
    $i['status'] = ($avaliacao === 'sim') ? 'Encerrado' : 'Reaberto';

    $encontrado = true;
    break;
  }
}

if (!$encontrado) {
  http_response_code(404);
  echo 'Inconformidade não encontrada.';
  exit;
}

file_put_contents($caminho, json_encode($inconformidades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo ($avaliacao === 'sim')
  ? 'Inconformidade encerrada com sucesso.'
  : 'Inconformidade reaberta para novo tratamento.';
