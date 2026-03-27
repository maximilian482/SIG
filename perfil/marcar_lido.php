<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/funcoes.php';
$conn = conectar();

// Entrada
$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$tipo = $_POST['tipo'] ?? 'reconhecimento';

// Normaliza o ID de funcionário da sessão
$funcionarioId = $_SESSION['id_funcionario'] ?? $_SESSION['funcionario_id'] ?? 0;
$funcionarioId = (int)$funcionarioId;

// Mapeia aliases de tipo
// interacao => reconhecimento
if ($tipo === 'interacao') {
  $tipo = 'reconhecimento';
}

// Validações
if ($funcionarioId <= 0) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão inválida.']);
  exit;
}
if ($id <= 0) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
  exit;
}

try {
  if ($tipo === 'reconhecimento') {
    // Confere titularidade
    $check = $conn->prepare("SELECT funcionario_id FROM reconhecimentos WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$res) {
      echo json_encode(['sucesso' => false, 'mensagem' => 'Registro não encontrado.']);
      exit;
    }
    if ((int)$res['funcionario_id'] !== $funcionarioId) {
      echo json_encode([
        'sucesso' => false,
        'mensagem' => "Este reconhecimento pertence ao funcionário {$res['funcionario_id']}, não ao logado {$funcionarioId}."
      ]);
      exit;
    }

    // Atualiza para lido
    $stmt = $conn->prepare("UPDATE reconhecimentos SET lido = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['sucesso' => (bool)$ok]);

  } elseif ($tipo === 'mensagem') {
    // Confere titularidade
    $check = $conn->prepare("SELECT destinatario_id FROM mensagens WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$res) {
      echo json_encode(['sucesso' => false, 'mensagem' => 'Mensagem não encontrada.']);
      exit;
    }
    if ((int)$res['destinatario_id'] !== $funcionarioId) {
      echo json_encode([
        'sucesso' => false,
        'mensagem' => "Esta mensagem pertence ao funcionário {$res['destinatario_id']}, não ao logado {$funcionarioId}."
      ]);
      exit;
    }

    // Atualiza para lida
    $stmt = $conn->prepare("UPDATE mensagens SET lida = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['sucesso' => (bool)$ok]);

  } else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Tipo inválido.']);
  }
} catch (Throwable $e) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no servidor: ' . $e->getMessage()]);
}
