<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';

$conn = conectar();

// Verifica login e usuário
if (empty($_SESSION['funcionario_id'])) {
    header('Location: /login.php');
    exit;
}

$erro = '';
$flash = getFlash(); // pega mensagem de sucesso/erro

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim((string)($_POST['titulo'] ?? ''));
    $data_inicio = trim((string)($_POST['data_inicio'] ?? '')) ?: null;
    $data_fim = trim((string)($_POST['data_fim'] ?? '')) ?: null;
    $descricao = trim((string)($_POST['descricao'] ?? ''));

    if ($titulo === '') {
        $erro = 'O título do plano é obrigatório.';
    }

    if (!$erro && $data_inicio && $data_fim) {
        if (strtotime($data_fim) < strtotime($data_inicio)) {
            $erro = 'A data final não pode ser anterior à data de início.';
        }
    }

    // prevenção de duplicatas rápidas
    if (!$erro) {
        $stmtDup = $conn->prepare("SELECT id FROM planos_acao WHERE titulo = ? LIMIT 1");
        if ($stmtDup) {
            $stmtDup->bind_param('s', $titulo);
            $stmtDup->execute();
            $resDup = $stmtDup->get_result();
            if ($resDup && $resDup->num_rows > 0) {
                $erro = 'Já existe um plano com esse título.';
            }
            $stmtDup->close();
        }
    }

    if (!$erro) {
        $sql = "INSERT INTO planos_acao (titulo, descricao, data_inicio, data_fim, criado_por, data_criacao, status)
                VALUES (?, ?, ?, ?, ?, NOW(), 'ativa')";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $criadoPor = intval($_SESSION['funcionario_id']);
            $data_inicio_db = $data_inicio ?: null;
            $data_fim_db = $data_fim ?: null;

            $stmt->bind_param('ssssi', $titulo, $descricao, $data_inicio_db, $data_fim_db, $criadoPor);

            if ($stmt->execute()) {
                setFlash('success', 'Plano criado com sucesso!');
                header('Location: planos_acao_listar.php');
                exit;
            } else {
                $erro = 'Erro ao salvar o plano: ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        } else {
            $erro = 'Erro interno ao preparar o registro.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Novo Plano de Ação</title>
<style>
    body {
        background:#f5f5f5;
        font-family:Arial, Helvetica, sans-serif;
        margin:0;
        padding:0;
    }

    .container {
        max-width:720px;
        margin:40px auto;
        background:#fff;
        padding:30px;
        border-radius:12px;
        box-shadow:0 4px 14px rgba(0,0,0,0.08);
        animation: fadeIn .25s ease-out;
    }

    @keyframes fadeIn {
        from { opacity:0; transform:translateY(10px); }
        to { opacity:1; transform:translateY(0); }
    }

    h2 {
        color:#006437;
        margin-top:0;
        border-left:6px solid #00A859;
        padding-left:12px;
        font-size:1.6rem;
        font-weight:700;
    }

    label {
        display:block;
        margin-top:18px;
        font-weight:600;
        color:#006437;
        font-size:1rem;
    }

    input, textarea {
        width:100%;
        padding:10px 12px;
        margin-top:6px;
        border-radius:8px;
        border:1px solid #cfcfcf;
        font-size:1rem;
        box-sizing:border-box;
        transition:all .2s ease;
        background:#fafafa;
    }

    input:focus, textarea:focus {
        border-color:#00A859;
        background:#fff;
        outline:none;
        box-shadow:0 0 0 2px rgba(0,168,89,0.15);
    }

    textarea {
        resize:vertical;
        min-height:110px;
    }

    button {
        margin-top:24px;
        padding:12px 18px;
        background:#006437;
        color:#fff;
        border:none;
        border-radius:8px;
        cursor:pointer;
        font-size:1rem;
        font-weight:600;
        transition:background .2s ease;
        width:100%;
    }

    button:hover {
        background:#008f4f;
    }

    .flash {
        padding:12px 16px;
        border-radius:8px;
        margin-bottom:18px;
        font-weight:600;
        font-size:0.95rem;
        border-left:6px solid;
    }

    .flash-success {
        background:#e6ffed;
        color:#1b5e20;
        border-color:#00A859;
    }

    .flash-error {
        background:#ffe6e6;
        color:#b30000;
        border-color:#cc0000;
    }

    @media (max-width:600px) {
        .container {
            margin:20px;
            padding:20px;
        }

        h2 {
            font-size:1.4rem;
        }
    }
</style>

</head>
<body>
<div class="container">
  <h2>Criar Novo Plano de Ação</h2>

  <?php if ($flash): ?>
      <div class="flash flash-<?= $flash['tipo'] ?>">
          <?= htmlspecialchars($flash['mensagem']) ?>
      </div>
  <?php endif; ?>

  <?php if (!empty($erro)): ?>
    <div class="flash flash-error"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="post">
    <label>Título *</label>
    <input type="text" name="titulo" required value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>">

    <label>Descrição</label>
    <textarea name="descricao" rows="4"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>

    <label>Data início</label>
    <input type="date" name="data_inicio" value="<?= htmlspecialchars($_POST['data_inicio'] ?? '') ?>">

    <label>Data fim</label>
    <input type="date" name="data_fim" value="<?= htmlspecialchars($_POST['data_fim'] ?? '') ?>">

    <button type="submit">Criar Plano</button>
  </form>
</div>
</body>
</html>
