<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require_once __DIR__ . '/../dados/conexao.php';

define('CAMINHO_BASE', '/projeto-loja'); // ajuste conforme o nome da pasta raiz no servidor

// Teste de conexão
if (!debug_backtrace()) {
  $conn = conectar();
  echo $conn ? '✅ Conexão bem-sucedida!' : '❌ Falha na conexão.';
}


// 🔐 Verifica login
if (!isset($_SESSION['usuario']) || !isset($_SESSION['cpf'])) {
  header('Location: /login.php');
  exit;
}

// 📷 Foto de perfil
function caminhoFotoPerfil($conn, $idFuncionario) {
  $foto = 'perfil.png';
  $caminho = CAMINHO_BASE . '/imagens/perfil.png';

  if ($idFuncionario) {
    $stmt = $conn->prepare("SELECT foto FROM funcionarios WHERE id = ?");
    $stmt->bind_param("i", $idFuncionario);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res && !empty($res['foto']) && file_exists(__DIR__ . "/../uploads/" . $res['foto'])) {
      $caminho = CAMINHO_BASE . '/uploads/' . $res['foto'];
    }
  }

  return $caminho;
}


// 🔑 Verifica permissão por módulo
function temAcesso($conn, $cpf, $modulo, $origem = '') {
  $permitido = false;

  // Consulta de acesso
  $stmt = $conn->prepare("
    SELECT acesso
    FROM acessos_usuarios
    WHERE cpf = ? AND modulo = ?
    LIMIT 1
  ");

  if (!$stmt) {
    error_log("Erro na preparação da consulta: " . $conn->error);
    return false;
  }

  $stmt->bind_param("ss", $cpf, $modulo);

  if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
      $permitido = isset($row['acesso']) && $row['acesso'] == 1;
    }
    $result->free();
  } else {
    error_log("Erro na execução da consulta: " . $stmt->error);
  }

  $stmt->close();

  // Registro de log
  $logStmt = $conn->prepare("
    INSERT INTO log_acessos (cpf, modulo, resultado, origem)
    VALUES (?, ?, ?, ?)
  ");

  if ($logStmt) {
    $resultado = $permitido ? 'permitido' : 'negado';
    $logStmt->bind_param("ssss", $cpf, $modulo, $resultado, $origem);
    $logStmt->execute();
    $logStmt->close();
  } else {
    error_log("Erro ao registrar log de acesso: " . $conn->error);
  }

  return $permitido;
}



// 🔤 Normaliza texto
function normalizar($texto) {
  $texto = strtolower(trim($texto));
  $texto = str_replace(["\n", "\r"], '', $texto);
  $texto = str_replace(['á','à','â','ã','ä'], 'a', $texto);
  $texto = str_replace(['é','è','ê','ë'], 'e', $texto);
  $texto = str_replace(['í','ì','î','ï'], 'i', $texto);
  $texto = str_replace(['ó','ò','ô','õ','ö'], 'o', $texto);
  $texto = str_replace(['ú','ù','û','ü'], 'u', $texto);
  $texto = str_replace(['ç'], 'c', $texto);
  return $texto;
}

// 📊 Contadores gerais
function contarFuncionarios($conn) {
  return $conn->query("SELECT COUNT(*) AS total FROM funcionarios WHERE desligamento IS NULL")->fetch_assoc()['total'] ?? 0;
}

function contarItensInventario($conn) {
  return $conn->query("SELECT COUNT(*) AS total FROM inventario WHERE baixa IS NULL")->fetch_assoc()['total'] ?? 0;
}

function contarLojas($conn) {
  return $conn->query("SELECT COUNT(*) AS total FROM lojas WHERE nome LIKE '%loja%' OR nome LIKE '%filial%'")->fetch_assoc()['total'] ?? 0;
}

// 📋 Chamados
function listarChamados($conn) {
  $chamados = [];
  $res = $conn->query("SELECT setor_destino, status, loja_origem FROM chamados");
  while ($row = $res->fetch_assoc()) {
    $chamados[] = $row;
  }
  return $chamados;
}

function contarPendenciasPorSetor($chamados, $setorAlvo) {
  $total = 0;
  $setorAlvo = normalizar($setorAlvo);
  $statusValidos = ['aberto', 'em andamento', 'reaberto', 'aguardando avaliação'];
  foreach ($chamados as $c) {
    $setor = normalizar($c['setor_destino'] ?? '');
    $status = normalizar($c['status'] ?? '');
    if ($setor === $setorAlvo && in_array($status, $statusValidos)) {
      $total++;
    }
  }
  return $total;
}

function contarChamadosLoja($chamados, $loja) {
  $total = 0;
  foreach ($chamados as $c) {
    $status = normalizar($c['status'] ?? '');
    if (!in_array($status, ['encerrado', 'cancelado', 'finalizado'])) {
      $total++;
    }
    if ($loja && normalizar($c['loja_origem'] ?? '') === normalizar($loja) && $status !== 'encerrado') {
      $total++;
    }
  }
  return $total;
}

// ⚠️ Inconformidades
function contarInconformidades($conn) {
  $res = $conn->query("SELECT loja_id, status FROM inconformidades");
  $total = 0;
  while ($i = $res->fetch_assoc()) {
    $status = normalizar($i['status'] ?? '');
    if (in_array($status, ['aberto', 'aguardando avaliação', 'reaberto'])) {
      $total++;
    }
  }
  return $total;
}

function contarInconformidadesLoja($conn, $lojaId) {
  $stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM inconformidades
    WHERE loja_id = ?
      AND status IN ('Aberto','Reaberto','Aguardando resposta','Aguardando avaliação')
  ");
  $stmt->bind_param("i", $lojaId);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  return $res['total'] ?? 0;
}
