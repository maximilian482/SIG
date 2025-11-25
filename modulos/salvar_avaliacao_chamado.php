<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();
date_default_timezone_set('America/Sao_Paulo');

header('Content-Type: application/json; charset=utf-8');

$id            = intval($_POST['id'] ?? 0);
$acao          = trim($_POST['acao'] ?? ''); // "setor_encerrar", "setor_andamento", "avaliacao_sim", "avaliacao_nao"
$resposta      = trim($_POST['resposta'] ?? '');
$justificativa = trim($_POST['justificativa'] ?? '');
$usuarioId     = intval($_SESSION['funcionario_id'] ?? 0);

if ($id <= 0 || $acao === '') {
  echo json_encode(['ok'=>false, 'mensagem'=>'❌ Dados inválidos.']);
  exit;
}

// Busca chamado
$stmt = $conn->prepare("SELECT solicitante_id FROM chamados WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$chamado = $stmt->get_result()->fetch_assoc();

if (!$chamado) {
  echo json_encode(['ok'=>false, 'mensagem'=>'❌ Chamado não encontrado.']);
  exit;
}

/* ---------- Fluxo do setor ---------- */
if ($acao === 'setor_encerrar' || $acao === 'setor_andamento') {
  if ($resposta === '') {
    echo json_encode(['ok'=>false, 'mensagem'=>'❌ É necessário informar a solução/resposta.']);
    exit;
  }

  if ($acao === 'setor_encerrar') {
    $novoStatus = 'Aguardando avaliação';
    $stmtUpd = $conn->prepare("
      UPDATE chamados
         SET solucao = ?, data_solucao = NOW(), status = ?, data_assumido = NOW()
       WHERE id = ?
    ");
    $stmtUpd->bind_param("ssi", $resposta, $novoStatus, $id);
  } else {
    $novoStatus = 'Em andamento';
    $stmtUpd = $conn->prepare("
      UPDATE chamados
         SET solucao = ?, status = ?, data_assumido = NOW()
       WHERE id = ?
    ");
    $stmtUpd->bind_param("ssi", $resposta, $novoStatus, $id);
  }

  if ($stmtUpd->execute()) {
    echo json_encode([
      'ok'=>true,
      'mensagem'=> ($acao === 'setor_encerrar')
        ? '✅ Resposta registrada. Chamado aguardando avaliação do solicitante.'
        : '🔄 Resposta registrada. Chamado mantido em andamento.'
    ]);
  } else {
    echo json_encode(['ok'=>false, 'mensagem'=>'❌ Erro ao salvar resposta.']);
  }
  exit;
}

/* ---------- Fluxo do solicitante ---------- */
if ($acao === 'avaliacao_sim' || $acao === 'avaliacao_nao') {
  if (intval($chamado['solicitante_id']) !== $usuarioId) {
    echo json_encode(['ok'=>false, 'mensagem'=>'❌ Você não é o solicitante deste chamado.']);
    exit;
  }

  if ($acao === 'avaliacao_sim') {
    $novoStatus   = 'Encerrado';
    $avaliacaoTxt = 'Satisfeito';
    $justificativa = null;
  } else {
    if ($justificativa === '') {
      echo json_encode(['ok'=>false, 'mensagem'=>'❌ Justificativa obrigatória quando não foi atendido.']);
      exit;
    }
    $novoStatus   = 'Reaberto';
    $avaliacaoTxt = 'Não atendido';
  }

  $stmtUpd = $conn->prepare("
    UPDATE chamados
       SET status = ?, avaliacao = ?, justificativa = ?, data_avaliacao = NOW()
     WHERE id = ?
  ");
  $stmtUpd->bind_param("sssi", $novoStatus, $avaliacaoTxt, $justificativa, $id);

  if ($stmtUpd->execute()) {
    echo json_encode([
      'ok'=>true,
      'mensagem'=> ($acao === 'avaliacao_sim')
        ? '✅ Atendimento aprovado. Chamado encerrado.'
        : '⚠️ Atendimento reprovado. Chamado reaberto.'
    ]);
  } else {
    echo json_encode(['ok'=>false, 'mensagem'=>'❌ Erro ao salvar avaliação.']);
  }
  exit;
}

echo json_encode(['ok'=>false, 'mensagem'=>'❌ Ação inválida.']);
